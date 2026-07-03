<?php

namespace App\Services;

use App\Models\EvaluationAnswer;
use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\Faculty;
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
            ->when($filters['subject_id'] ?? null, fn (Builder $query, mixed $subjectId) => $query->whereHas('subjectMapping', fn (Builder $mapping) => $mapping->where('subject_id', $subjectId)))
            ->when($filters['section_id'] ?? null, fn (Builder $query, mixed $sectionId) => $query->whereHas('subjectMapping', fn (Builder $mapping) => $mapping->where('section_id', $sectionId)))
            ->when($filters['school_year'] ?? null, fn (Builder $query, mixed $schoolYear) => $query->whereHas('form', fn (Builder $form) => $form->where('school_year', $schoolYear)))
            ->when($filters['semester'] ?? null, fn (Builder $query, mixed $semester) => $query->whereHas('form', fn (Builder $form) => $form->where('semester', $semester)))
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
            'facultyOptions' => Faculty::query()->orderBy('faculty_name')->get(),
            'mappingOptions' => SubjectMapping::query()->with(['subject', 'section'])->latest()->get(),
        ];
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
