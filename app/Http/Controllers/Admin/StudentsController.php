<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentAssignmentRequest;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\Department;
use App\Models\CourseCreation;
use App\Models\Section;
use App\Models\StudentSectionAllocation;
use App\Models\StudentEnrollment;
use App\Models\SubjectMapping;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentsController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $studentQuery = User::query()->where('role', User::ROLE_STUDENT)->with(['department', 'section', 'subjectMappings.subject', 'subjectMappings.section', 'sectionAllocations.section'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->string('search').'%')->orWhere('student_number', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name');

        $importedQuery = StudentEnrollment::query()
            ->where('role', 'student')
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
                ->when($request->filled('section_id'), fn ($query) => $query
                    ->where('section_id', (string) $request->integer('section_id')))
                ->when($request->filled('status'), fn ($query) => $query
                    ->where('status', $request->string('status')))
                ->with(['account', 'course', 'section.department', 'department'])
            ->orderBy('user_id')
            ->orderBy('course_id');

        [$students, $importedEnrollments, $directoryPagination] =
            $this->paginateDirectory($studentQuery, $importedQuery, $request);

        return view('admin.students', [
            'students' => $students,
            'departments' => Department::query()->orderBy('department_name')->get(),
            'sections' => Section::query()->with('department')->orderBy('section_name')->get(),
            'mappings' => SubjectMapping::query()->with(['subject', 'section', 'faculty'])->orderByDesc('school_year')->get(),
            'importedEnrollments' => $importedEnrollments,
            'importedCourses' => CourseCreation::query()->orderBy('short_name')->get(),
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

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $student = DB::transaction(function () use ($data, $request) {
            $student = User::query()->create(collect($data)->except(['password_confirmation'])->all() + ['role' => User::ROLE_STUDENT]);
            $this->recordSection($student, $request->user()?->id, null, 'Initial assignment');

            return $student;
        });
        $this->logger->log($request, 'STUDENT_CREATED', "Created student {$student->student_number} ({$student->name}).");

        return back()->with('success', 'Student record created.');
    }

    public function update(StudentRequest $request, User $student): RedirectResponse
    {
        $this->studentOnly($student);
        $data = $request->validated();
        DB::transaction(function () use ($student, $data, $request) {
            $oldSection = $student->section_id;
            if (empty($data['password'])) {
                unset($data['password']);
            }
            unset($data['password_confirmation']);
            $student->update($data);
            if ($oldSection !== $student->section_id) {
                $this->recordSection($student, $request->user()?->id, $oldSection, 'Profile update');
            }
        });
        $this->logger->log($request, 'STUDENT_UPDATED', "Updated student {$student->student_number}.");

        return back()->with('success', 'Student record updated.');
    }

    public function destroy(Request $request, User $student): RedirectResponse
    {
        $this->studentOnly($student);
        $student->delete();
        $this->logger->log($request, 'STUDENT_ARCHIVED', "Archived student {$student->student_number}.");

        return back()->with('success', 'Student record archived safely.');
    }

    public function updateImported(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'section_id' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($enrollment, $data) {
            $enrollment->account?->update([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                ...(filled($data['password'] ?? null) ? ['password' => $data['password']] : []),
            ]);
            $enrollment->update([
                'section_id' => $data['section_id'] ?: null,
                'department_id' => ($data['department_id'] ?? null) ?: null,
                'status' => $data['status'],
            ]);
        });
        $this->logger->log($request, 'IMPORTED_STUDENT_UPDATED', "Updated imported enrollment for {$enrollment->user_id}.");

        return back()->with('success', 'Imported student record updated.');
    }

    public function assignImportedSubject(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $data = $request->validate(['subject_mapping_id' => ['required', 'integer', 'exists:subject_mappings,id']]);
        $mapping = SubjectMapping::query()->with(['subject', 'section'])->findOrFail($data['subject_mapping_id']);
        $enrollment->update([
            'course_id' => $mapping->subject->subject_code,
            'section_id' => (string) $mapping->section_id,
            'department_id' => $mapping->section->department_id,
        ]);
        $this->logger->log($request, 'IMPORTED_SUBJECT_ASSIGNED', "Assigned mapping {$mapping->id} to imported student {$enrollment->user_id}.");

        return back()->with('success', 'Subject assigned to imported student.');
    }

    public function destroyImported(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $userId = $enrollment->user_id;
        $enrollment->delete();
        $this->logger->log($request, 'IMPORTED_STUDENT_ARCHIVED', "Archived imported enrollment for {$userId}.");

        return back()->with('success', 'Imported student archived.');
    }

    public function assignSubject(StudentAssignmentRequest $request, User $student): RedirectResponse
    {
        $this->studentOnly($student);
        $mapping = SubjectMapping::query()->with('section')->findOrFail($request->integer('subject_mapping_id'));
        if (! $student->section_id) {
            return back()->withErrors(['subject_mapping_id' => 'Assign the student to a section before assigning subjects.']);
        }
        if ($mapping->section_id !== $student->section_id) {
            return back()->withErrors(['subject_mapping_id' => 'This subject mapping belongs to a different section.']);
        }
        $student->subjectMappings()->syncWithoutDetaching([$mapping->id]);
        $this->logger->log($request, 'STUDENT_SUBJECT_ASSIGNED', "Assigned mapping {$mapping->id} to student {$student->student_number}.");

        return back()->with('success', 'Subject assigned to student.');
    }

    public function unassignSubject(Request $request, User $student, SubjectMapping $mapping): RedirectResponse
    {
        $this->studentOnly($student);
        if ($mapping->evaluationResponses()->where('user_id', $student->id)->exists()) {
            return back()->withErrors(['assignment' => 'Cannot remove a subject after this student has submitted an evaluation.']);
        }
        $student->subjectMappings()->detach($mapping->id);
        $this->logger->log($request, 'STUDENT_SUBJECT_UNASSIGNED', "Removed mapping {$mapping->id} from student {$student->student_number}.");

        return back()->with('success', 'Subject unassigned from student.');
    }

    private function recordSection(User $student, ?int $actorId, ?int $oldSection, string $reason): void
    {
        StudentSectionAllocation::query()->where('user_id', $student->id)->whereNull('ended_at')->update(['ended_at' => now()]);
        if ($student->section_id) {
            StudentSectionAllocation::query()->create(['user_id' => $student->id, 'section_id' => $student->section_id, 'changed_by' => $actorId, 'assigned_at' => now(), 'reason' => $reason]);
        }
    }

    private function studentOnly(User $student): void
    {
        abort_unless($student->role === User::ROLE_STUDENT, 404);
    }
}
