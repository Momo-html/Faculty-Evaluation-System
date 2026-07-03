<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationAnswer;
use App\Services\SentimentAnalyzer;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SentimentController extends Controller
{
    public function __invoke(SentimentAnalyzer $sentimentAnalyzer): View
    {
        if (! Schema::hasTable('evaluation_answers')) {
            return view('admin.sentiment', ['feedbacks' => collect()]);
        }

        $feedbacks = EvaluationAnswer::query()
            ->with(['question', 'response.subjectMapping.faculty', 'response.subjectMapping.subject'])
            ->whereNotNull('text_answer')
            ->latest()
            ->get()
            ->groupBy(fn (EvaluationAnswer $answer): string => $answer->response?->subjectMapping?->faculty?->faculty_name ?? 'Unassigned Faculty')
            ->map(function ($answers) use ($sentimentAnalyzer) {
                return $answers->map(function (EvaluationAnswer $answer) use ($sentimentAnalyzer): object {
                    $mapping = $answer->response?->subjectMapping;

                    return (object) [
                        'subject_code' => $mapping?->subject?->subject_code ?? 'N/A',
                        'subject_name' => $mapping?->subject?->subject_name ?? 'No subject',
                        'comment' => $answer->text_answer,
                        'sentiment' => $sentimentAnalyzer->classify($answer->text_answer),
                        'submitted_at' => $answer->created_at,
                    ];
                });
            });

        return view('admin.sentiment', ['feedbacks' => $feedbacks]);
    }
}
