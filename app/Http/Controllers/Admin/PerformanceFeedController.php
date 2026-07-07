<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EvaluationForm;
use App\Models\ExportLog;
use App\Models\Faculty;
use App\Models\PdfReport;
use App\Services\AuditLogger;
use App\Services\EvaluationReportService;
use App\Support\SettingsSupport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PerformanceFeedController extends Controller
{
    public function index(Request $request, EvaluationReportService $reports): View
    {
        $filters = $this->filters($request);

        if (! Schema::hasTable('evaluation_responses')) {
            return view('admin.sentiment', [
                'filters' => $filters,
                'forms' => collect(),
                'departments' => collect(),
                'facultyOptions' => collect(),
                'subjectOptions' => collect(),
                'sectionOptions' => collect(),
                'academicYearOptions' => collect(),
                'semesterOptions' => collect(),
                'departmentPerformance' => collect(),
                'facultyPerformance' => collect(),
                'summary' => [],
                'comments' => collect(),
                'ratingScaleMax' => SettingsSupport::ratingScaleMax(),
                'performanceLegend' => SettingsSupport::performanceScale(),
                'minimumReliableResponses' => SettingsSupport::minimumReliableResponses(),
                'pdfExportAllowed' => false,
            ]);
        }

        return view('admin.sentiment', $reports->performanceFeed($filters) + [
            'filters' => $filters,
            'pdfExportAllowed' => SettingsSupport::enabled('allow_pdf_export', true),
        ]);
    }

    public function exportSelectedDepartmentPdf(Request $request, EvaluationReportService $reports, AuditLogger $auditLogger)
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        return $this->exportDepartmentPdf($request, Department::query()->findOrFail($validated['department_id']), $reports, $auditLogger);
    }

    public function exportDepartmentPdf(Request $request, Department $department, EvaluationReportService $reports, AuditLogger $auditLogger)
    {
        abort_if(SettingsSupport::enabled('allow_pdf_export', true) === false, 403, 'PDF export is disabled in settings.');

        $filters = $this->filters($request) + ['department_id' => $department->id];
        $data = $reports->departmentReport($filters);
        $fileName = 'department-performance-'.$department->id.'-'.now()->format('Ymd-His').'.pdf';

        ExportLog::query()->create([
            'exported_by' => $request->user()?->id,
            'evaluation_form_id' => $filters['form_id'] ?? null,
            'file_name' => $fileName,
            'file_type' => 'pdf',
            'export_type' => 'department_performance_report',
            'exported_at' => now(),
        ]);

        $auditLogger->record(
            $request,
            'EXPORT',
            'Performance Feed / PDF Export',
            $department,
            'Exported Department Performance PDF for '.$department->department_name,
            null,
            [
                'user_name' => $request->user()?->name,
                'role' => $request->user()?->role,
                'department_id' => $department->id,
                'filters' => $filters,
            ],
        );

        return Pdf::loadView('admin.reports.department_pdf', $data + [
            'filters' => $filters,
            'settings' => SettingsSupport::all(),
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
        ])->setPaper('a4', 'landscape')->download($fileName);
    }

    public function exportSelectedFacultyPdf(Request $request, EvaluationReportService $reports, AuditLogger $auditLogger)
    {
        $validated = $request->validate([
            'faculty_id' => ['required', 'exists:faculty,id'],
        ]);

        return $this->exportFacultyPdf($request, Faculty::query()->findOrFail($validated['faculty_id']), $reports, $auditLogger);
    }

    public function exportFacultyPdf(Request $request, Faculty $faculty, EvaluationReportService $reports, AuditLogger $auditLogger)
    {
        abort_if(SettingsSupport::enabled('allow_pdf_export', true) === false, 403, 'PDF export is disabled in settings.');

        $filters = $this->filters($request) + ['faculty_id' => $faculty->id];
        $form = isset($filters['form_id'])
            ? EvaluationForm::query()->find($filters['form_id'])
            : EvaluationForm::query()->where('is_active', true)->latest()->first();
        $filters['form_id'] ??= $form?->id;
        $data = $reports->individualFacultyReport($faculty, $filters);
        $fileName = 'faculty-performance-'.$faculty->id.'-'.now()->format('Ymd-His').'.pdf';

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
            'export_type' => 'individual_performance_report',
            'exported_at' => now(),
        ]);

        $auditLogger->record(
            $request,
            'EXPORT',
            'Performance Feed / PDF Export',
            $faculty,
            'Exported Individual Professor Performance PDF for '.$faculty->faculty_name,
            null,
            [
                'user_name' => $request->user()?->name,
                'role' => $request->user()?->role,
                'faculty_id' => $faculty->id,
                'filters' => $filters,
            ],
        );

        return Pdf::loadView('admin.reports.individual_faculty_pdf', $data + [
            'filters' => $filters,
            'settings' => SettingsSupport::all(),
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
            'exportFaculty' => $faculty,
        ])->setPaper('a4')->download($fileName);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'school_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'faculty_id' => ['nullable', 'exists:faculty,id'],
            'form_id' => ['nullable', 'exists:evaluation_forms,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
    }
}
