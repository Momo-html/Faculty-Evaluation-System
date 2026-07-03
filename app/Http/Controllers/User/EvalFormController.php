<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\SubjectMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EvalFormController extends Controller
{
    public function __invoke(): View
    {
        return view('user.eval-form', [
            'form' => null,
            'evaluation' => (object) [
                'mapping_id' => null,
                'form_id' => null,
                'subject_code' => 'N/A',
                'subject_name' => 'No active evaluation selected',
                'faculty_name' => 'N/A',
            ],
            'questions' => collect(),
        ]);
    }

    public function show(Request $request, int $mapping): View
    {
        $activeForm = EvaluationForm::query()
            ->with('questions')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('open_at')->orWhere('open_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('close_at')->orWhere('close_at', '>=', now());
            })
            ->latest()
            ->firstOrFail();

        abort_unless($this->studentCanAnswerMapping($request->user()->id, $mapping), 403, 'This evaluation is not assigned to your account.');

        $subjectMapping = SubjectMapping::query()
            ->with(['subject', 'faculty', 'section'])
            ->findOrFail($mapping);

        $alreadySubmitted = EvaluationResponse::query()
            ->where('evaluation_form_id', $activeForm->id)
            ->where('subject_mapping_id', $subjectMapping->id)
            ->where('user_id', $request->user()->id)
            ->whereNotNull('submitted_at')
            ->exists();

        abort_if($alreadySubmitted, 403, 'You already submitted this evaluation.');

        return view('user.eval-form', [
            'form' => $activeForm,
            'evaluation' => (object) [
                'mapping_id' => $subjectMapping->id,
                'form_id' => $activeForm->id,
                'subject_code' => $subjectMapping->subject?->subject_code ?? 'N/A',
                'subject_name' => $subjectMapping->subject?->subject_name ?? 'No subject',
                'faculty_name' => $subjectMapping->faculty?->faculty_name ?? 'Unassigned faculty',
            ],
            'questions' => $activeForm->questions,
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'form_id' => ['required', 'exists:evaluation_forms,id'],
            'mapping_id' => ['required', 'exists:subject_mappings,id'],
            'rating' => ['nullable', 'array'],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string', 'max:3000'],
            'choice' => ['nullable', 'array'],
            'choice.*' => ['nullable', 'string', 'max:255'],
        ]);

        $form = EvaluationForm::query()
            ->with('questions')
            ->whereKey($validated['form_id'])
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('open_at')->orWhere('open_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('close_at')->orWhere('close_at', '>=', now());
            })
            ->firstOrFail();

        abort_unless($this->studentCanAnswerMapping($request->user()->id, (int) $validated['mapping_id']), 403, 'This evaluation is not assigned to your account.');

        $alreadySubmitted = EvaluationResponse::query()
            ->where('evaluation_form_id', $form->id)
            ->where('subject_mapping_id', $validated['mapping_id'])
            ->where('user_id', $request->user()->id)
            ->whereNotNull('submitted_at')
            ->exists();

        abort_if($alreadySubmitted, 403, 'You already submitted this evaluation.');

        $this->validateAnswersForForm($request, $form);

        DB::transaction(function () use ($request, $validated, $form): void {
            $ratings = collect($validated['rating'] ?? [])->filter(fn ($value): bool => $value !== null && $value !== '');

            $response = EvaluationResponse::query()->create([
                'evaluation_form_id' => $form->id,
                'user_id' => $request->user()->id,
                'subject_mapping_id' => $validated['mapping_id'],
                'overall_score' => $ratings->isNotEmpty() ? round($ratings->avg(), 2) : null,
                'submitted_at' => now(),
            ]);

            foreach ($ratings as $questionId => $value) {
                EvaluationAnswer::query()->updateOrCreate(
                    ['evaluation_response_id' => $response->id, 'form_question_id' => $questionId],
                    ['rating_value' => $value, 'text_answer' => null],
                );
            }

            foreach (($validated['comments'] ?? []) as $questionId => $text) {
                if (trim((string) $text) === '') {
                    continue;
                }

                EvaluationAnswer::query()->updateOrCreate(
                    ['evaluation_response_id' => $response->id, 'form_question_id' => $questionId],
                    ['rating_value' => null, 'text_answer' => $text],
                );
            }

            foreach (($validated['choice'] ?? []) as $questionId => $choice) {
                if (trim((string) $choice) === '') {
                    continue;
                }

                EvaluationAnswer::query()->updateOrCreate(
                    ['evaluation_response_id' => $response->id, 'form_question_id' => $questionId],
                    ['rating_value' => null, 'text_answer' => $choice],
                );
            }
        });

        return redirect()
            ->route('user.home')
            ->with('success', 'Evaluation submitted. Thank you for your feedback.');
    }

    private function studentCanAnswerMapping(int $userId, int $mappingId): bool
    {
        $hasEnrollmentRows = DB::table('student_subjects')->exists();

        if (! $hasEnrollmentRows) {
            return SubjectMapping::query()->whereKey($mappingId)->exists();
        }

        return DB::table('student_subjects')
            ->where('user_id', $userId)
            ->where('subject_mapping_id', $mappingId)
            ->exists();
    }

    private function validateAnswersForForm(Request $request, EvaluationForm $form): void
    {
        $errors = [];

        foreach ($form->questions as $question) {
            $type = $question->question_type;
            $rating = $request->input("rating.{$question->id}");
            $choice = $request->input("choice.{$question->id}");
            $comment = $request->input("comments.{$question->id}");

            if ($question->is_required && blank($rating) && blank($choice) && blank($comment)) {
                $errors["question_{$question->id}"] = 'Please answer all required questions.';
                continue;
            }

            if ($type === 'rating' && ! blank($rating)) {
                $min = (int) data_get($question->options, 'scale_min', 1);
                $max = (int) data_get($question->options, 'scale_max', 5);

                if (! is_numeric($rating) || (int) $rating < $min || (int) $rating > $max) {
                    $errors["rating.{$question->id}"] = "Rating must be between {$min} and {$max}.";
                }
            }

            if ($type === 'multiple_choice' && ! blank($choice)) {
                $validOptions = collect($question->options ?? [])->filter()->values()->all();

                if (! in_array($choice, $validOptions, true)) {
                    $errors["choice.{$question->id}"] = 'Selected option is not valid for this question.';
                }
            }
        }

        if ($errors !== []) {
            validator([], [])->after(function ($validator) use ($errors): void {
                foreach ($errors as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            })->validate();
        }
    }
}
