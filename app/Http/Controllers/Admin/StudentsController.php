<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentAssignmentRequest;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\Department;
use App\Models\Section;
use App\Models\StudentSectionAllocation;
use App\Models\SubjectMapping;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentsController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $students = User::query()->where('role', User::ROLE_STUDENT)->with(['department', 'section', 'subjectMappings.subject', 'subjectMappings.section', 'sectionAllocations.section'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->string('search').'%')->orWhere('student_number', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.students', [
            'students' => $students,
            'departments' => Department::query()->orderBy('department_name')->get(),
            'sections' => Section::query()->with('department')->orderBy('section_name')->get(),
            'mappings' => SubjectMapping::query()->with(['subject', 'section', 'faculty'])->orderByDesc('school_year')->get(),
        ]);
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
