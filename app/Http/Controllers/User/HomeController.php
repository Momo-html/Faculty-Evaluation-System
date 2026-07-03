<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\SubjectMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        if (! Schema::hasTable('evaluation_forms')) {
            return view('user.home', [
                'availableEvaluations' => collect(),
                'completedEvaluations' => [],
                'activeForm' => null,
            ]);
        }

        $activeForm = EvaluationForm::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('open_at')->orWhere('open_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('close_at')->orWhere('close_at', '>=', now());
            })
            ->latest()
            ->first();

        $studentMappingIds = DB::table('student_subjects')
            ->where('user_id', auth()->id())
            ->pluck('subject_mapping_id');
        $hasEnrollmentRows = DB::table('student_subjects')->exists();

        $availableEvaluations = $activeForm
            ? SubjectMapping::query()
                ->with(['subject', 'faculty', 'section'])
                ->when($hasEnrollmentRows, fn ($query) => $query->whereIn('id', $studentMappingIds))
                ->get()
                ->map(fn (SubjectMapping $mapping): object => (object) [
                    'mapping_id' => $mapping->id,
                    'form_id' => $activeForm->id,
                    'subject_code' => $mapping->subject?->subject_code ?? 'N/A',
                    'subject_name' => $mapping->subject?->subject_name ?? 'No subject',
                    'faculty_name' => $mapping->faculty?->faculty_name ?? 'Unassigned faculty',
                ])
            : collect();

        $completedEvaluations = $activeForm
            ? EvaluationResponse::query()
                ->where('user_id', auth()->id())
                ->where('evaluation_form_id', $activeForm->id)
                ->whereNotNull('submitted_at')
                ->pluck('subject_mapping_id')
                ->all()
            : [];

        return view('user.home', compact('availableEvaluations', 'completedEvaluations', 'activeForm'));
    }
}
