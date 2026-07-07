<?php

namespace App\Services;

use App\Models\EvaluationAnswer;
use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectMapping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EvaluationReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function facultyReport(array $filters = []): array
    {
        $responses = EvaluationResponse::query()
            ->with(['form', 'answers.question', 'subjectMapping.faculty', 'subjectMapping.subject', 'subjectMapping.section'])
            ->whereNotNull('submitted_at')
            ->when($filters['form_id'] ?? null, fn (Builder $query, mixed $formId) => $query->where('evaluation_form_id', $formId))
            ->when($filters['faculty_id'] ?? null, fn (Builder $query, mixed $facultyId) => $query->whereHas('subjectMapping', fn (Builder $mapping) => $mapping->where('faculty_id', $facultyId)))
            ->when($filters['department_id'] ?? null, fn (Builder $query, mixed $departmentId) => $query->whereHas('subjectMapping.faculty', fn (Builder $faculty) => $faculty->where('department_id', $departmentId)))
            ->when($filters['subject_id'] ?? null, fn (Builder $query, mixed $subjectId) => $query->whereHas('subjectMapping', fn (Builder $mapping) => $mapping->where('subject_id', $subjectId)))
            ->when($filters['section_id'] ?? null, fn (Builder $query, mixed $sectionId) => $query->whereHas('subjectMapping', fn (Builder $mapping) => $mapping->where('section_id', $sectionId)))
            ->when($filters['school_year'] ?? null, fn (Builder $query, mixed $schoolYear) => $query->whereHas('form', fn (Builder $form) => $form->where('school_year', $schoolYear)))
            ->when($filters['semester'] ?? null, fn (Builder $query, mixed $semester) => $query->whereHas('form', fn (Builder $form) => $form->where('semester', $semester)))
            ->when($filters['date_from'] ?? null, fn (Builder $query, mixed $dateFrom) => $query->whereDate('submitted_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $query, mixed $dateTo) => $query->whereDate('submitted_at', '<=', $dateTo))
            ->latest('submitted_at')
            ->get();

        $ratingAnswers = EvaluationAnswer::query()
            ->whereNotNull('rating_value')
            ->whereIn('evaluation_response_id', $responses->pluck('id'))
            ->get();

        $comments = EvaluationAnswer::query()
            ->with(['question', 'response.subjectMapping.faculty', 'response.subjectMapping.subject', 'response.subjectMapping.section'])
            ->whereNotNull('text_answer')
            ->whereIn('evaluation_response_id', $responses->pluck('id'))
            ->latest()
            ->get();

        $byFaculty = $responses
            ->groupBy(fn (EvaluationResponse $response): string => (string) $response->subjectMapping?->faculty_id)
            ->map(function (Collection $group, string $facultyId) use ($ratingAnswers): array {
                $faculty = $group->first()?->subjectMapping?->faculty;
                $answerIds = $group->pluck('id');
                $facultyRatings = $ratingAnswers->whereIn('evaluation_response_id', $answerIds);

                return [
                    'faculty_id' => (int) $facultyId,
                    'faculty_name' => $faculty?->faculty_name ?? 'Unassigned Faculty',
                    'respondents' => $group->count(),
                    'average_score' => round((float) $facultyRatings->avg('rating_value'), 2),
                    'subjects' => $group->pluck('subjectMapping.subject.subject_name')->filter()->unique()->values(),
                    'sections' => $group->pluck('subjectMapping.section.section_name')->filter()->unique()->values(),
                ];
            })
            ->values();

        return [
            'responses' => $responses,
            'ratingAnswers' => $ratingAnswers,
            'comments' => $comments,
            'byFaculty' => $byFaculty,
            'overallAverage' => round((float) $ratingAnswers->avg('rating_value'), 2),
            'totalRespondents' => $responses->count(),
            'forms' => EvaluationForm::query()->latest()->get(),
            'departments' => Department::query()->orderBy('department_name')->get(),
            'facultyOptions' => Faculty::query()->orderBy('faculty_name')->get(),
            'subjectOptions' => Subject::query()->orderBy('subject_code')->get(),
            'sectionOptions' => Section::query()->orderBy('section_name')->get(),
            'mappingOptions' => SubjectMapping::query()->with(['subject', 'section'])->latest()->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function departmentReport(array $filters): array
    {
        $data = $this->facultyReport($filters);
        $department = Department::query()->find($filters['department_id'] ?? null);
        $responses = $data['responses'];
        $ratingAnswers = $data['ratingAnswers'];

        $facultyRows = $responses
            ->groupBy(fn (EvaluationResponse $response): string => implode(':', [
                $response->subjectMapping?->faculty_id,
                $response->subjectMapping?->subject_id,
                $response->subjectMapping?->section_id,
            ]))
            ->map(function (Collection $group) use ($ratingAnswers): array {
                $mapping = $group->first()?->subjectMapping;
                $answerIds = $group->pluck('id');
                $ratings = $ratingAnswers->whereIn('evaluation_response_id', $answerIds);

                return [
                    'faculty_name' => $mapping?->faculty?->faculty_name ?? 'Unassigned Faculty',
                    'department' => $mapping?->faculty?->department?->department_name ?? 'No Department',
                    'subject' => $mapping?->subject?->subject_name ?? 'N/A',
                    'section' => $mapping?->section?->section_name ?? 'N/A',
                    'respondents' => $group->count(),
                    'average_rating' => round((float) $ratings->avg('rating_value'), 2),
                    'interpretation' => $this->interpretRating((float) $ratings->avg('rating_value')),
                    'remarks' => $ratings->isNotEmpty() ? 'With submitted evaluations' : 'No rating answers',
                ];
            })
            ->sortByDesc('average_rating')
            ->values();

        $categoryAverages = $ratingAnswers
            ->groupBy(fn (EvaluationAnswer $answer): string => $answer->question?->category ?: 'Uncategorized')
            ->map(fn (Collection $answers, string $category): array => [
                'category' => $category,
                'average' => round((float) $answers->avg('rating_value'), 2),
                'responses' => $answers->count(),
            ])
            ->values();

        return $data + [
            'department' => $department,
            'facultyRows' => $facultyRows,
            'categoryAverages' => $categoryAverages,
            'totalFaculty' => Faculty::query()->when($department, fn (Builder $query) => $query->where('department_id', $department->id))->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function individualFacultyReport(Faculty $faculty, array $filters = []): array
    {
        $filters['faculty_id'] = $faculty->id;
        $data = $this->facultyReport($filters);
        $responses = $data['responses'];
        $ratingAnswers = $data['ratingAnswers']->load('question');

        $questionRows = $ratingAnswers
            ->groupBy('form_question_id')
            ->map(function (Collection $answers): array {
                $question = $answers->first()?->question;

                return [
                    'question' => $question?->question_text ?? 'Question',
                    'category' => $question?->category ?: 'Uncategorized',
                    'question_type' => $question?->question_type ?? 'rating',
                    'average_score' => round((float) $answers->avg('rating_value'), 2),
                    'total_responses' => $answers->count(),
                    'interpretation' => $this->interpretRating((float) $answers->avg('rating_value')),
                ];
            })
            ->values();

        $categoryRows = $ratingAnswers
            ->groupBy(fn (EvaluationAnswer $answer): string => $answer->question?->category ?: 'Uncategorized')
            ->map(fn (Collection $answers, string $category): array => [
                'category' => $category,
                'average_score' => round((float) $answers->avg('rating_value'), 2),
                'total_responses' => $answers->count(),
            ])
            ->values();

        $subjectRows = $responses
            ->groupBy(fn (EvaluationResponse $response): string => (string) $response->subjectMapping?->subject_id)
            ->map(function (Collection $group) use ($ratingAnswers): array {
                $subject = $group->first()?->subjectMapping?->subject;
                $ratings = $ratingAnswers->whereIn('evaluation_response_id', $group->pluck('id'));

                return [
                    'subject' => $subject?->subject_name ?? 'N/A',
                    'respondents' => $group->count(),
                    'average_score' => round((float) $ratings->avg('rating_value'), 2),
                ];
            })
            ->values();

        $sectionRows = $responses
            ->groupBy(fn (EvaluationResponse $response): string => (string) $response->subjectMapping?->section_id)
            ->map(function (Collection $group) use ($ratingAnswers): array {
                $section = $group->first()?->subjectMapping?->section;
                $ratings = $ratingAnswers->whereIn('evaluation_response_id', $group->pluck('id'));

                return [
                    'section' => $section?->section_name ?? 'N/A',
                    'respondents' => $group->count(),
                    'average_score' => round((float) $ratings->avg('rating_value'), 2),
                ];
            })
            ->values();

        return $data + [
            'faculty' => $faculty->load('department'),
            'questionRows' => $questionRows,
            'categoryRows' => $categoryRows,
            'subjectRows' => $subjectRows,
            'sectionRows' => $sectionRows,
        ];
    }

    public function interpretRating(float $rating): string
    {
        return match (true) {
            $rating >= 4.5 => 'Excellent',
            $rating >= 3.5 => 'Very Satisfactory',
            $rating >= 2.5 => 'Satisfactory',
            $rating > 0 => 'Needs Improvement',
            default => 'No Rating',
        };
    }

    public function expectedRespondents(?EvaluationForm $form = null): int
    {
        if (! $form) {
            return 0;
        }

        return SubjectMapping::query()
            ->withCount('responses')
            ->get()
            ->sum(fn (SubjectMapping $mapping): int => max($mapping->responses_count, 1));
    }
}
