<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2933; font-size: 11px; }
        .header { border-bottom: 4px solid #274c07; padding-bottom: 12px; margin-bottom: 14px; }
        .brand { display: table; width: 100%; }
        .brand-logo, .brand-text { display: table-cell; vertical-align: middle; }
        .brand-logo { width: 74px; }
        .brand-logo img { max-width: 62px; max-height: 62px; object-fit: contain; }
        h1 { color: #274c07; font-size: 21px; margin: 0; }
        h2 { color: #274c07; font-size: 14px; margin: 15px 0 7px; }
        .muted { color: #667085; }
        .accent-line { height: 3px; background: #8aa57d; margin-top: 8px; }
        .intro { margin: 10px 0 12px; }
        .summary { width: 100%; margin: 12px 0; border-collapse: collapse; }
        .summary td { border: 1px solid #dfe7dc; padding: 8px; width: 25%; }
        .metric { color: #274c07; font-size: 17px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #274c07; color: #fff; text-align: left; }
        th, td { border: 1px solid #dfe7dc; padding: 7px; vertical-align: top; }
        .legend span { display: inline-block; border: 1px solid #dfe7dc; border-radius: 10px; margin: 0 5px 5px 0; padding: 4px 8px; }
        .remarks { border: 1px solid #dfe7dc; min-height: 58px; padding: 9px; }
        .footer { margin-top: 18px; border-top: 1px solid #dfe7dc; padding-top: 8px; font-size: 10px; color: #667085; }
        .signature { margin-top: 34px; width: 240px; border-top: 1px solid #1f2933; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>
    @php
        $show = fn (string $key): bool => ($settings[$key] ?? '1') === '1';
        $pdfLogoPath = \App\Support\SettingsSupport::imagePathForPdf('school_logo_path');
        $formName = optional($forms->firstWhere('id', $filters['form_id'] ?? null))->title ?? 'All Forms';
        $subjectText = $subjectRows->pluck('subject')->filter()->unique()->join(', ') ?: 'All Subjects';
        $sectionText = $sectionRows->pluck('section')->filter()->unique()->join(', ') ?: 'All Sections';
    @endphp

    <div class="header">
        <div class="brand">
            @if($show('individual_pdf_school_logo') && $pdfLogoPath)
                <div class="brand-logo"><img src="{{ $pdfLogoPath }}" alt="School logo"></div>
            @endif
            <div class="brand-text">
                @if($show('individual_pdf_school_name'))
                    <strong>{{ $settings['school_name'] ?? 'FEU Cavite' }}</strong><br>
                @endif
                @if($show('individual_pdf_system_name'))
                    <span class="muted">{{ $settings['system_name'] ?? 'Faculty Evaluation Portal' }}</span>
                @endif
                @if($show('individual_pdf_report_title'))
                    <h1>{{ $settings['individual_report_title'] ?? $settings['default_report_title'] ?? 'Individual Professor Performance Report' }}</h1>
                @endif
                <div class="muted">
                    @if($show('individual_pdf_faculty_name')) Professor: {{ $faculty->faculty_name }} | @endif
                    @if($show('individual_pdf_department')) Department: {{ $faculty->department?->department_name ?? 'No Department' }} | @endif
                    Evaluation Form: {{ $formName }}
                </div>
            </div>
        </div>
        <div class="accent-line"></div>
    </div>

    @if(filled($settings['individual_report_intro'] ?? null))
        <p class="intro">{{ $settings['individual_report_intro'] }}</p>
    @endif

    <table class="summary">
        <tr>
            @if($show('individual_pdf_academic_year'))
                <td><b>Academic Year</b><br>{{ $filters['school_year'] ?? 'All' }}</td>
            @endif
            @if($show('individual_pdf_semester'))
                <td><b>Semester</b><br>{{ $filters['semester'] ?? 'All' }}</td>
            @endif
            @if($show('individual_pdf_subject'))
                <td><b>Subject / Course</b><br>{{ $subjectText }}</td>
            @endif
            @if($show('individual_pdf_section'))
                <td><b>Section</b><br>{{ $sectionText }}</td>
            @endif
        </tr>
        <tr>
            @if($show('individual_pdf_total_respondents'))
                <td><b>Total Respondents</b><br><span class="metric">{{ $totalRespondents }}</span></td>
            @endif
            @if($show('individual_pdf_overall_average'))
                <td><b>Overall Average Rating</b><br><span class="metric">{{ number_format($overallAverage, 2) }} / {{ number_format($ratingScaleMax, 2) }}</span></td>
            @endif
            @if($show('individual_pdf_classification'))
                <td><b>Performance Classification</b><br><span class="metric">{{ $overallClassification }}</span></td>
            @endif
            @if($show('individual_pdf_reliability_indicator'))
                <td><b>Reliability Indicator</b><br>{{ $reliabilityIndicator['label'] }}</td>
            @endif
        </tr>
        <tr>
            <td><b>Date Generated</b><br>{{ $generatedAt->format('F d, Y h:i A') }}</td>
            <td><b>Generated By</b><br>{{ $generatedBy?->name ?? 'System' }}</td>
            <td><b>Rating Scale</b><br>1.00 - {{ number_format($ratingScaleMax, 2) }}</td>
            <td><b>Source</b><br>Submitted quantitative ratings only</td>
        </tr>
    </table>

    @if($show('individual_pdf_average_per_question'))
        <h2>Rating per Question</h2>
        <table>
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Category</th>
                    <th>Question Type</th>
                    <th>Average Rating</th>
                    <th>Total Responses</th>
                    <th>Classification</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questionRows as $row)
                    <tr>
                        <td>{{ $row['question'] }}</td>
                        <td>{{ $row['category'] }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $row['question_type'])) }}</td>
                        <td>{{ number_format($row['average_score'], 2) }} / {{ number_format($ratingScaleMax, 2) }}</td>
                        <td>{{ $row['total_responses'] }}</td>
                        <td>{{ $row['interpretation'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;">No rating answers available.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if($show('individual_pdf_average_per_category'))
        <h2>Rating per Category</h2>
        <table>
            <thead><tr><th>Category</th><th>Average Rating</th><th>Total Responses</th></tr></thead>
            <tbody>
                @forelse($categoryRows as $row)
                    <tr><td>{{ $row['category'] }}</td><td>{{ number_format($row['average_score'], 2) }} / {{ number_format($ratingScaleMax, 2) }}</td><td>{{ $row['total_responses'] }}</td></tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;">No category ratings available.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <h2>Performance Interpretation Legend</h2>
    <div class="legend">
        @foreach($performanceLegend as $legend)
            <span>{{ number_format($legend['min'], 2) }} - {{ number_format($legend['max'], 2) }} = {{ $legend['label'] }}</span>
        @endforeach
    </div>

    @if($show('individual_pdf_admin_remarks'))
        <h2>{{ $settings['admin_remarks_label'] ?? 'Admin Remarks' }}</h2>
        <div class="remarks"></div>
    @endif

    @if($show('individual_pdf_signature_line'))
        <div class="signature">{{ $settings['signature_label'] ?? 'Authorized Signature' }}</div>
        <div class="muted">{{ $settings['prepared_by_label'] ?? 'Prepared by' }}: {{ $generatedBy?->name ?? 'System' }}</div>
    @endif

    @if($show('individual_pdf_footer_text'))
        <div class="footer">{{ $settings['individual_report_footer_text'] ?? $settings['footer_text'] ?? '' }}</div>
    @endif
</body>
</html>
