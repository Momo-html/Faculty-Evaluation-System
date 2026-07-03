<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EvaluationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'school_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:50'],
            'open_at' => ['required', 'date'],
            'close_at' => ['required', 'date', 'after:open_at'],
            'is_active' => ['nullable', 'boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'integer', 'exists:form_questions,id'],
            'questions.*.text' => ['required', 'string', 'max:1000'],
            'questions.*.type' => ['required', 'string', 'in:Scale,Text,Multiple Choice,rating,text,multiple_choice'],
            'questions.*.category' => ['nullable', 'string', 'max:100'],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.scale_min' => ['nullable', 'integer', 'min:0', 'max:10'],
            'questions.*.scale_max' => ['nullable', 'integer', 'min:1', 'max:10'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('questions', []) as $index => $question) {
                    $type = strtolower((string) ($question['type'] ?? ''));
                    $type = str_replace(' ', '_', $type);

                    if (in_array($type, ['scale', 'rating'], true)) {
                        $min = (int) ($question['scale_min'] ?? 1);
                        $max = (int) ($question['scale_max'] ?? 5);

                        if ($min >= $max) {
                            $validator->errors()->add("questions.{$index}.scale_max", 'The rating maximum must be greater than the minimum.');
                        }
                    }

                    if ($type === 'multiple_choice') {
                        $options = collect($question['options'] ?? [])
                            ->map(fn ($option) => trim((string) $option))
                            ->filter()
                            ->unique()
                            ->values();

                        if ($options->count() < 2) {
                            $validator->errors()->add("questions.{$index}.options", 'Multiple choice questions need at least two unique options.');
                        }
                    }
                }
            },
        ];
    }
}
