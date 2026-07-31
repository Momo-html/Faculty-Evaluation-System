<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacultyAssignmentRequest;
use App\Http\Requests\Admin\FacultyRequest;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectMapping;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FacultyController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $facultyQuery = Faculty::query()->with(['department', 'subjectMappings.subject', 'subjectMappings.section'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('faculty_name', 'like', '%'.$request->string('search').'%')
                ->orWhere('employee_id', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('faculty_name');

        $importedQuery = StudentEnrollment::query()
            ->whereIn('role', ['faculty', 'teacher', 'instructor', 'teacher_enrollment'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search')->trim().'%';

                    $query->where(function ($inner) use ($search) {
                        $inner->where('user_id', 'like', $search)
                            ->orWhereHas('account', fn ($account) => $account
                                ->where('full_name', 'like', $search)
                                ->orWhere('short_name', 'like', $search)
                                ->orWhere('login_id', 'like', $search)
                                ->orWhere('email', 'like', $search));
                    });
                })
                ->when($request->filled('department_id'), fn ($query) => $query
                    ->where('department_id', $request->integer('department_id')))
                ->when($request->filled('status'), fn ($query) => $query
                    ->where('status', $request->string('status')))
                ->with(['account', 'course', 'section.department', 'department'])
            ->orderBy('user_id')
            ->orderBy('course_id');

        [$faculty, $importedFacultyEnrollments, $directoryPagination] =
            $this->paginateDirectory($facultyQuery, $importedQuery, $request);

        return view('admin.faculty', [
            'faculty' => $faculty,
            'departments' => Department::query()->orderBy('department_name')->get(),
            'sections' => Section::query()->with('department')->orderBy('section_name')->get(),
            'subjects' => Subject::query()->orderBy('subject_code')->get(),
            'importedFacultyEnrollments' => $importedFacultyEnrollments,
            'directoryPagination' => $directoryPagination,
        ]);
    }

    private function paginateDirectory($primaryQuery, $importedQuery, Request $request): array
    {
        $perPage = 10;
        $page = max(1, $request->integer('page', 1));
        $offset = ($page - 1) * $perPage;
        $primaryCount = (clone $primaryQuery)->count();
        $importedCount = (clone $importedQuery)->count();

        $primary = $offset < $primaryCount
            ? $primaryQuery->offset($offset)->limit($perPage)->get()
            : collect();
        $remaining = $perPage - $primary->count();
        $importedOffset = max(0, $offset - $primaryCount);
        $imported = $remaining > 0
            ? $importedQuery->offset($importedOffset)->limit($remaining)->get()
            : collect();

        $pagination = new LengthAwarePaginator(
            [],
            $primaryCount + $importedCount,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return [$primary, $imported, $pagination];
    }

    public function store(FacultyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureUserEmailAvailable($data['email']);

        $faculty = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['faculty_name'], 'email' => $data['email'], 'password' => Str::random(40),
                'role' => User::ROLE_FACULTY, 'department_id' => $data['department_id'], 'status' => $data['status'],
            ]);

            return Faculty::query()->create($data + ['user_id' => $user->id]);
        });

        $this->logger->log($request, 'FACULTY_CREATED', "Created faculty {$faculty->employee_id} ({$faculty->faculty_name}).");

        return back()->with('success', 'Faculty profile created.');
    }

    public function update(FacultyRequest $request, Faculty $faculty): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureUserEmailAvailable($data['email'], $faculty->user_id);

        DB::transaction(function () use ($data, $faculty) {
            $userData = ['name' => $data['faculty_name'], 'email' => $data['email'], 'department_id' => $data['department_id'], 'status' => $data['status']];
            if ($faculty->user) {
                $faculty->user->update($userData);
            } else {
                $userData += ['password' => Str::random(40), 'role' => User::ROLE_FACULTY];
                $faculty->user_id = User::query()->create($userData)->id;
            }
            $faculty->update($data);
        });

        $this->logger->log($request, 'FACULTY_UPDATED', "Updated faculty {$faculty->employee_id}.");

        return back()->with('success', 'Faculty profile updated.');
    }

    public function destroy(Request $request, Faculty $faculty): RedirectResponse
    {
        DB::transaction(function () use ($faculty) {
            $faculty->delete();
            $faculty->user?->delete();
        });
        $this->logger->log($request, 'FACULTY_ARCHIVED', "Archived faculty {$faculty->employee_id}.");

        return back()->with('success', 'Faculty profile archived safely.');
    }

    public function updateImported(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'section_id' => ['nullable', 'string', 'max:255'],
            'course_id' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($enrollment, $data) {
            $enrollment->account?->update([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
            ]);
            $enrollment->update([
                'department_id' => $data['department_id'] ?: null,
                'section_id' => $data['section_id'] ?: null,
                'course_id' => $data['course_id'],
                'status' => $data['status'],
            ]);
        });
        $this->logger->log($request, 'IMPORTED_FACULTY_UPDATED', "Updated imported faculty enrollment for {$enrollment->user_id}.");

        return back()->with('success', 'Imported faculty record updated.');
    }

    public function destroyImported(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $userId = $enrollment->user_id;
        $enrollment->delete();
        $this->logger->log($request, 'IMPORTED_FACULTY_ARCHIVED', "Archived imported faculty enrollment for {$userId}.");

        return back()->with('success', 'Imported faculty archived.');
    }

    public function assignSubject(FacultyAssignmentRequest $request, Faculty $faculty): RedirectResponse
    {
        $data = $request->validated();
        $section = Section::query()->findOrFail($data['section_id']);
        $subject = Subject::query()->findOrFail($data['subject_id']);
        if ($section->department_id !== $faculty->department_id || ($subject->department_id && $subject->department_id !== $faculty->department_id)) {
            return back()->withErrors(['assignment' => 'Faculty, section, and departmental subject must belong to compatible departments.']);
        }
        $mapping = SubjectMapping::query()->firstOrCreate($data + ['faculty_id' => $faculty->id]);
        $this->logger->log($request, 'FACULTY_SUBJECT_ASSIGNED', "Assigned mapping {$mapping->id} to faculty {$faculty->employee_id}.");

        return back()->with('success', 'Subject and section assigned to faculty.');
    }

    public function unassignSubject(Request $request, Faculty $faculty, SubjectMapping $mapping): RedirectResponse
    {
        abort_unless($mapping->faculty_id === $faculty->id, 404);
        if ($mapping->students()->exists() || $mapping->evaluationResponses()->exists()) {
            return back()->withErrors(['assignment' => 'Cannot remove this mapping because students or evaluations already use it.']);
        }
        $mapping->delete();
        $this->logger->log($request, 'FACULTY_SUBJECT_UNASSIGNED', "Removed mapping {$mapping->id} from faculty {$faculty->employee_id}.");

        return back()->with('success', 'Faculty assignment removed.');
    }

    private function ensureUserEmailAvailable(string $email, ?int $ignoreId = null): void
    {
        if (User::withTrashed()->where('email', $email)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            throw ValidationException::withMessages(['email' => 'This email is already used by another login account.']);
        }
    }
}
