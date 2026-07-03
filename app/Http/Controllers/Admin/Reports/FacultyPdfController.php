<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\ExportLog;
use App\Models\Faculty;
use App\Models\PdfReport;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\EvaluationReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FacultyPdfController extends Controller
{
    public function __invoke(Request $request, EvaluationReportService $reports): View
    {
        if (! Schema::hasTable('evaluation_responses')) {
            return view('admin.reports.faculty_pdf', [
                'filters' => [],
                'forms' => collect(),
                'facultyOptions' => collect(),
                'byFaculty' => collect(),
                'comments' => collect(),
                'overallAverage' => 0,
                'totalRespondents' => 0,
                'pdfExportAllowed' => false,
                'generatedAt' => now(),
            ]);
        }

        $filters = $request->only(['form_id', 'faculty_id', 'subject_id', 'section_id', 'school_year', 'semester']);
        $data = $reports->facultyReport($filters);

        return view('admin.reports.faculty_pdf', $data + [
            'filters' => $filters,
            'pdfExportAllowed' => Setting::value('allow_pdf_export', '1') === '1',
            'generatedAt' => now(),
        ]);
    }

    public function export(Request $request, Faculty $faculty, EvaluationReportService $reports, AuditLogger $auditLogger)
    {
        abort_if(Setting::value('allow_pdf_export', '1') !== '1', 403, 'PDF export is disabled in settings.');

        $form = EvaluationForm::query()->where('is_active', true)->latest()->first();
        $data = $reports->facultyReport([
            'faculty_id' => $faculty->id,
            'form_id' => $request->integer('form_id') ?: $form?->id,
        ]);

        $fileName = 'faculty-evaluation-'.$faculty->id.'-'.now()->format('Ymd-His').'.pdf';
        $pdf = Pdf::loadView('admin.reports.faculty_pdf', $data + [
            'filters' => ['faculty_id' => $faculty->id, 'form_id' => $form?->id],
            'pdfExportAllowed' => false,
            'generatedAt' => now(),
            'exportFaculty' => $faculty,
        ])->setPaper('a4');

        if ($form) {
            PdfReport::query()->create([
                'evaluation_form_id' => $form->id,
                'faculty_id' => $faculty->id,
                'generated_by' => $request->user()?->id,
                'file_name' => $fileName,
                'file_path' => 'downloaded/'.$fileName,
                'report_status' => 'generated',
                'generated_at' => now(),
            ]);
        }

        ExportLog::query()->create([
            'exported_by' => $request->user()?->id,
            'evaluation_form_id' => $form?->id,
            'file_name' => $fileName,
            'file_type' => 'pdf',
            'export_type' => 'faculty_evaluation_report',
            'exported_at' => now(),
        ]);

        $auditLogger->record($request, 'EXPORT', 'Faculty Evaluation Reports', $faculty, 'Exported faculty evaluation PDF for '.$faculty->faculty_name);

        return $pdf->download($fileName);
    }
}
