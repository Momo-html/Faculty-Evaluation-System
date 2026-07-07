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
use App\Support\SettingsSupport;
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
    public function performanceFeed(array $filters = []): array
    {
        $data = $this->facultyReport($filters);
        $responses = $data['responses'];
        $ratingAnswers = $data['ratingAnswers']->load('question');

        $departmentPerformance = $responses
            ->groupBy(fn (EvaluationResponse $response): string => (string) ($response->subjectMapping?->faculty?->department_id ?? 'unassigned'))
            ->map(function (Collection $group) use ($ratingAnswers): array {
                $department = $group->first()?->subjectMapping?->faculty?->department;
                $ratings = $ratingAnswers->whereIn('evaluation_response_id', $group->pluck('id'));
                $average = round((float) $ratings->avg('rating_value'), 2);
                $classification = SettingsSupport::classifyRating($average);

                return [
                    'department_id' => $department?->id,
                    'department_name' => $department?->department_name ?? 'Unassigned Department',
                    'faculty_count' => $group->pluck('subjectMapping.faculty_id')->filter()->unique()->count(),
                    'responses' => $group->count(),
                    'average_rating' => $average,
                    'classification' => $classification,
                    'classification_class' => SettingsSupport::classificationClass($classification),
                ];
            })
            ->sortBy('department_name')
            ->values();

        $facultyPerformance = $responses
            ->groupBy(fn (EvaluationResponse $response): string => (string) ($response->subjectMapping?->faculty_id ?? 'unassigned'))
            ->map(function (Collection $group) use ($ratingAnswers): array {
                $mapping = $group->first()?->subjectMapping;
                $faculty = $mapping?->faculty;
                $ratings = $ratingAnswers->whereIn('evaluation_response_id', $group->pluck('id'));
                $average = round((float) $ratings->avg('rating_value'), 2);
                $classification = SettingsSupport::classifyRating($average);
                $reliability = SettingsSupport::reliabilityFor($group->count());

                return [
                    'faculty_id' => $faculty?->id,
                    'faculty_name' => $faculty?->faculty_name ?? 'Unassigned Professor',
                    'department_name' => $faculty?->department?->department_name ?? 'No Department',
                    'subjects' => $group->pluck('subjectMapping.subject.subject_name')->filter()->unique()->values(),
                    'sections' => $group->pluck('subjectMapping.section.section_name')->filter()->unique()->values(),
                    'responses' => $group->count(),
                    'average_rating' => $average,
                    'classification' => $classification,
                    'classification_class' => SettingsSupport::classificationClass($classification),
                    'reliability' => $reliability['label'],
                    'reliability_class' => $reliability['class'],
                ];
            })
            ->sortBy('faculty_name')
            ->values();

        $highestDepartment = $departmentPerformance
            ->filter(fn (array $department): bool => $department['average_rating'] > 0)
            ->sortByDesc('average_rating')
            ->first();

        $monitoringClasses = ['Needs Improvement', 'Poor'];

        return $data + [
            'departmentPerformance' => $departmentPerformance,
            'facultyPerformance' => $facultyPerformance,
            'summary' => [
                'total_faculty_evaluated' => $facultyPerformance->whereNotNull('faculty_id')->count(),
                'total_departments' => $departmentPerformance->whereNotNull('department_id')->count(),
                'total_evaluation_responses' => $responses->count(),
                'overall_average_rating' => round((float) $ratingAnswers->avg('rating_value'), 2),
                'highest_department_average' => $highestDepartment,
                'faculty_needing_monitoring' => $facultyPerformance
                    ->filter(fn (array $faculty): bool => in_array($faculty['classification'], $monitoringClasses, true) || $faculty['reliability_class'] === 'low')
                    ->count(),
            ],
            'academicYearOptions' => collect()
                ->merge(EvaluationForm::query()->pluck('school_year'))
                ->merge(SubjectMapping::query()->pluck('school_year'))
                ->filter()
                ->unique()
                ->sort()
                ->values(),
            'semesterOptions' => collect()
                ->merge(EvaluationForm::query()->pluck('semester'))
                ->merge(SubjectMapping::query()->pluck('semester'))
                ->filter()
                ->unique()
                ->sort()
                ->values(),
            'performanceLegend' => SettingsSupport::performanceScale(),
            'ratingScaleMax' => SettingsSupport::ratingScaleMax(),
            'minimumReliableResponses' => SettingsSupport::minimumReliableResponses(),
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
                    'faculty_id' => $mapping?->faculty?->id,
                    'faculty_name' => $mapping?->faculty?->faculty_name ?? 'Unassigned Faculty',
                    'department' => $mapping?->faculty?->department?->department_name ?? 'No Department',
                    'subject' => $mapping?->subject?->subject_name ?? 'N/A',
                    'section' => $mapping?->section?->section_name ?? 'N/A',
                    'respondents' => $group->count(),
                    'average_rating' => round((float) $ratings->avg('rating_value'), 2),
                    'interpretation' => $this->interpretRating((float) $ratings->avg('rating_value')),
                    'classification_class' => SettingsSupport::classificationClass($this->interpretRating((float) $ratings->avg('rating_value'))),
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
            'totalFaculty' => $facultyRows->pluck('faculty_id')->filter()->unique()->count(),
            'overallClassification' => $this->interpretRating((float) $ratingAnswers->avg('rating_value')),
            'ratingScaleMax' => SettingsSupport::ratingScaleMax(),
            'performanceLegend' => SettingsSupport::performanceScale(),
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
            'overallClassification' => $this->interpretRating((float) $ratingAnswers->avg('rating_value')),
            'reliabilityIndicator' => SettingsSupport::reliabilityFor($responses->count()),
            'ratingScaleMax' => SettingsSupport::ratingScaleMax(),
            'performanceLegend' => SettingsSupport::performanceScale(),
        ];
    }

    public function interpretRating(float $rating): string
    {
        return SettingsSupport::classifyRating($rating);
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
