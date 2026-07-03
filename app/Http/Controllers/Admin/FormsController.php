<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationFormRequest;
use App\Models\EvaluationForm;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FormsController extends Controller
{
    public function __invoke(): View
    {
        if (! Schema::hasTable('evaluation_forms')) {
            return view('admin.forms', [
                'allForms' => new LengthAwarePaginator(collect(), 0, 10),
                'builderStats' => ['total' => 0, 'active' => 0, 'closed' => 0, 'questions' => 0],
            ]);
        }

        $forms = EvaluationForm::query()
            ->withCount(['questions', 'responses'])
            ->latest()
            ->paginate(10);

        return view('admin.forms', [
            'allForms' => $forms,
            'builderStats' => [
                'total' => EvaluationForm::query()->count(),
                'active' => EvaluationForm::query()->where('is_active', true)->count(),
                'closed' => EvaluationForm::query()->where('is_active', false)->count(),
                'questions' => DB::table('form_questions')->whereNull('deleted_at')->count(),
            ],
        ]);
    }

    public function show(EvaluationForm $form): JsonResponse
    {
        $form->load('questions');

        return response()->json([
            'id' => $form->id,
            'title' => $form->title,
            'description' => $form->description,
            'school_year' => $form->school_year,
            'semester' => $form->semester,
            'open_at' => optional($form->open_at)->format('Y-m-d H:i:s'),
            'close_at' => optional($form->close_at)->format('Y-m-d H:i:s'),
            'is_active' => $form->is_active,
            'questions' => $form->questions->map(fn ($question): array => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'type' => $this->toUiType($question->question_type),
                'question_type' => $question->question_type,
                'category' => $question->category,
                'options' => $this->choiceOptions($question->options ?? []),
                'scale_min' => (int) data_get($question->options, 'scale_min', 1),
                'scale_max' => (int) data_get($question->options, 'scale_max', 5),
                'is_required' => $question->is_required,
            ]),
        ]);
    }

    public function store(EvaluationFormRequest $request, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $formId = $request->integer('form_id') ?: null;

        $form = DB::transaction(function () use ($request, $validated, $formId, $auditLogger): EvaluationForm {
            $form = $formId
                ? EvaluationForm::query()->with('questions')->findOrFail($formId)
                : new EvaluationForm(['created_by' => $request->user()?->id]);

            $oldValues = $form->exists ? $form->toArray() : null;

            $form->fill([
                'title' => ($validated['title'] ?? null) ?: 'Faculty Evaluation '.$validated['school_year'].' '.$validated['semester'],
                'description' => $validated['description'] ?? null,
                'school_year' => $validated['school_year'],
                'semester' => $validated['semester'],
                'open_at' => $validated['open_at'] ?? null,
                'close_at' => $validated['close_at'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ])->save();

            if ($form->is_active) {
                EvaluationForm::query()
                    ->whereKeyNot($form->id)
                    ->where('semester', $form->semester)
                    ->where('school_year', $form->school_year)
                    ->update(['is_active' => false]);
            }

            $keptQuestionIds = [];
            foreach ($validated['questions'] as $index => $questionData) {
                $questionType = $this->normalizeType($questionData['type']);
                $question = $form->questions()
                    ->when($questionData['id'] ?? null, fn ($query, $id) => $query->whereKey($id))
                    ->firstOrNew();

                $question->fill([
                    'question_text' => $questionData['text'],
                    'question_type' => $questionType,
                    'category' => $questionData['category'] ?? null,
                    'options' => $this->optionsForQuestion($questionType, $questionData),
                    'order_number' => $index + 1,
                    'is_required' => (bool) ($questionData['is_required'] ?? true),
                ])->save();

                $keptQuestionIds[] = $question->id;
            }

            $form->questions()
                ->whereNotIn('id', $keptQuestionIds)
                ->get()
                ->each(function ($question): void {
                    $question->answers()->exists() ? $question->delete() : $question->forceDelete();
                });

            $auditLogger->record(
                $request,
                $formId ? 'UPDATE' : 'CREATE',
                'Evaluation Form Builder',
                $form,
                ($formId ? 'Updated' : 'Created').' evaluation form: '.$form->title,
                $oldValues,
                $form->fresh('questions')->toArray(),
            );

            return $form;
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'form_id' => $form->id]);
        }

        return redirect()->route('admin.forms')->with('success', 'Evaluation form saved.');
    }

    public function toggleStatus(EvaluationForm $form, AuditLogger $auditLogger): JsonResponse
    {
        $oldValues = $form->only(['is_active']);
        $form->update(['is_active' => ! $form->is_active]);

        if ($form->is_active) {
            EvaluationForm::query()
                ->whereKeyNot($form->id)
                ->where('semester', $form->semester)
                ->where('school_year', $form->school_year)
                ->update(['is_active' => false]);
        }

        $auditLogger->record(
            request(),
            'UPDATE',
            'Evaluation Form Builder',
            $form,
            ($form->is_active ? 'Activated' : 'Deactivated').' evaluation form: '.$form->title,
            $oldValues,
            $form->only(['is_active']),
        );

        return response()->json([
            'success' => true,
            'is_active' => $form->is_active,
            'message' => $form->is_active ? 'Form activated.' : 'Form deactivated.',
        ]);
    }

    public function reorderQuestions(Request $request, EvaluationForm $form, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['required', 'integer', 'exists:form_questions,id'],
        ]);

        foreach ($validated['questions'] as $index => $questionId) {
            $form->questions()->whereKey($questionId)->update(['order_number' => $index + 1]);
        }

        $auditLogger->record(
            $request,
            'UPDATE',
            'Evaluation Form Builder',
            $form,
            'Reordered questions for '.$form->title,
            null,
            ['question_order' => $validated['questions']],
        );

        return response()->json(['success' => true, 'message' => 'Question order saved.']);
    }

    public function destroy(EvaluationForm $form, AuditLogger $auditLogger): JsonResponse
    {
        if ($form->responses()->exists()) {
            $form->update(['is_active' => false]);
            $form->delete();
            $message = 'Form has responses, so it was archived instead of permanently deleted.';
        } else {
            $form->questions()->forceDelete();
            $form->forceDelete();
            $message = 'Form deleted successfully.';
        }

        $auditLogger->record(
            request(),
            'DELETE',
            'Evaluation Form Builder',
            $form,
            $message,
            $form->toArray(),
        );

        return response()->json(['success' => true, 'message' => $message]);
    }

    private function normalizeType(string $type): string
    {
        return match (strtolower($type)) {
            'scale', 'rating' => 'rating',
            'multiple choice', 'multiple_choice' => 'multiple_choice',
            default => 'text',
        };
    }

    private function toUiType(string $type): string
    {
        return match ($type) {
            'rating' => 'Scale',
            'multiple_choice' => 'Multiple Choice',
            default => 'Text',
        };
    }

    /**
     * @param  array<string, mixed>  $questionData
     * @return array<string, mixed>|list<string>
     */
    private function optionsForQuestion(string $questionType, array $questionData): array
    {
        if ($questionType === 'rating') {
            return [
                'scale_min' => (int) ($questionData['scale_min'] ?? 1),
                'scale_max' => (int) ($questionData['scale_max'] ?? 5),
            ];
        }

        if ($questionType === 'multiple_choice') {
            return collect($questionData['options'] ?? [])
                ->map(fn ($option) => trim((string) $option))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>|list<string>  $options
     * @return list<string>
     */
    private function choiceOptions(array $options): array
    {
        if (array_key_exists('scale_min', $options) || array_key_exists('scale_max', $options)) {
            return [];
        }

        return array_values(array_filter($options, fn ($option): bool => is_string($option) && trim($option) !== ''));
    }
}
