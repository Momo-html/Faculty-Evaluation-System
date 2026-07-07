<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EvaluationForm;
use App\Models\ExportLog;
use App\Models\Faculty;
use App\Models\PdfReport;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\EvaluationReportService;
use App\Support\SettingsSupport;
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
                'departments' => collect(),
                'facultyOptions' => collect(),
                'subjectOptions' => collect(),
                'sectionOptions' => collect(),
                'byFaculty' => collect(),
                'comments' => collect(),
                'overallAverage' => 0,
                'totalRespondents' => 0,
                'pdfExportAllowed' => false,
                'generatedAt' => now(),
            ]);
        }

        $filters = $request->only(['form_id', 'department_id', 'faculty_id', 'subject_id', 'section_id', 'school_year', 'semester', 'date_from', 'date_to']);
        $data = $reports->facultyReport($filters);

        return view('admin.reports.faculty_pdf', $data + [
            'filters' => $filters,
            'pdfExportAllowed' => Setting::value('allow_pdf_export', '1') === '1',
            'generatedAt' => now(),
        ]);
    }

    public function export(Request $request, Faculty $faculty, EvaluationReportService $reports, AuditLogger $auditLogger)
    {
        return $this->exportFacultyPdf($request, $reports, $auditLogger, $faculty);
    }

    public function exportDepartmentPdf(Request $request, EvaluationReportService $reports, AuditLogger $auditLogger)
    {
        abort_if(SettingsSupport::enabled('allow_pdf_export', true) === false, 403, 'PDF export is disabled in settings.');

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'form_id' => ['nullable', 'exists:evaluation_forms,id'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $department = Department::query()->findOrFail($validated['department_id']);
        $data = $reports->departmentReport($validated);
        $fileName = 'department-evaluation-'.$department->id.'-'.now()->format('Ymd-His').'.pdf';

        ExportLog::query()->create([
            'exported_by' => $request->user()?->id,
            'evaluation_form_id' => $validated['form_id'] ?? null,
            'file_name' => $fileName,
            'file_type' => 'pdf',
            'export_type' => 'department_evaluation_report',
            'exported_at' => now(),
        ]);

        $auditLogger->record(
            $request,
            'EXPORT',
            'Reports / PDF Export',
            $department,
            'Exported Department Evaluation PDF for '.$department->department_name,
            null,
            ['filters' => $validated],
        );

        return Pdf::loadView('admin.reports.department_pdf', $data + [
            'filters' => $validated,
            'settings' => SettingsSupport::all(),
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
        ])->setPaper('a4', 'landscape')->download($fileName);
    }

    public function exportFacultyPdf(Request $request, EvaluationReportService $reports, AuditLogger $auditLogger, ?Faculty $faculty = null)
    {
        abort_if(Setting::value('allow_pdf_export', '1') !== '1', 403, 'PDF export is disabled in settings.');

        $validated = $request->validate([
            'faculty_id' => [$faculty ? 'nullable' : 'required', 'exists:faculty,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'form_id' => ['nullable', 'exists:evaluation_forms,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'string', 'max:50'],
        ]);

        $faculty ??= Faculty::query()->findOrFail($validated['faculty_id']);
        $form = isset($validated['form_id']) ? EvaluationForm::query()->find($validated['form_id']) : EvaluationForm::query()->where('is_active', true)->latest()->first();
        $filters = $validated + ['faculty_id' => $faculty->id, 'form_id' => $validated['form_id'] ?? $form?->id];
        $data = $reports->individualFacultyReport($faculty, $filters);

        $fileName = 'faculty-evaluation-'.$faculty->id.'-'.now()->format('Ymd-His').'.pdf';
        $pdf = Pdf::loadView('admin.reports.individual_faculty_pdf', $data + [
            'filters' => $filters,
            'settings' => SettingsSupport::all(),
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
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

        $auditLogger->record(
            $request,
            'EXPORT',
            'Reports / PDF Export',
            $faculty,
            'Exported Individual Faculty Evaluation PDF for '.$faculty->faculty_name,
            null,
            ['filters' => $filters],
        );

        return $pdf->download($fileName);
    }
}
