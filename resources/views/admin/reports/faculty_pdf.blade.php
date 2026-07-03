<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #333; font-size: 12px; }
        .header { border-bottom: 3px solid #006835; padding-bottom: 12px; margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: bold; color: #006835; }
        .muted { color: #666; }
        .toolbar { margin: 16px 0; padding: 12px; background: #f7f8f5; border: 1px solid #e4e8df; }
        .btn { display: inline-block; padding: 8px 12px; background: #006835; color: #fff; text-decoration: none; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #006835; color: white; text-align: left; }
        th, td { border: 1px solid #ddd; padding: 9px; vertical-align: top; }
        .score { font-size: 24px; color: #006835; font-weight: bold; }
        .comment { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Faculty Evaluation Report</div>
        <div class="muted">Generated: {{ ($generatedAt ?? now())->format('F d, Y h:i A') }}</div>
        @isset($exportFaculty)
            <div><b>Faculty:</b> {{ $exportFaculty->faculty_name }}</div>
        @endisset
    </div>

    @if($pdfExportAllowed ?? false)
        <form method="GET" action="{{ route('admin.reports.faculty-pdf') }}" class="toolbar">
            <label>Form</label>
            <select name="form_id">
                <option value="">All Forms</option>
                @foreach($forms as $form)
                    <option value="{{ $form->id }}" @selected(($filters['form_id'] ?? '') == $form->id)>
                        {{ $form->title }} - {{ $form->school_year }} {{ $form->semester }}
                    </option>
                @endforeach
            </select>

            <label>Faculty</label>
            <select name="faculty_id">
                <option value="">All Faculty</option>
                @foreach($facultyOptions as $faculty)
                    <option value="{{ $faculty->id }}" @selected(($filters['faculty_id'] ?? '') == $faculty->id)>
                        {{ $faculty->faculty_name }}
                    </option>
                @endforeach
            </select>

            <button class="btn" type="submit">Filter</button>
        </form>
    @endif

    <table>
        <tr>
            <td><b>Total Submitted Evaluations</b><br><span class="score">{{ $totalRespondents }}</span></td>
            <td><b>Overall Average Rating</b><br><span class="score">{{ number_format($overallAverage, 2) }}</span></td>
        </tr>
    </table>

    <h3>Faculty Summary</h3>
    <table>
        <thead>
            <tr>
                <th>Faculty</th>
                <th>Respondents</th>
                <th>Average Score</th>
                <th>Subjects</th>
                <th>Sections</th>
                @if($pdfExportAllowed ?? false)
                    <th>Export</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($byFaculty as $row)
                <tr>
                    <td>{{ $row['faculty_name'] }}</td>
                    <td>{{ $row['respondents'] }}</td>
                    <td>{{ number_format($row['average_score'], 2) }}</td>
                    <td>{{ $row['subjects']->join(', ') ?: 'N/A' }}</td>
                    <td>{{ $row['sections']->join(', ') ?: 'N/A' }}</td>
                    @if($pdfExportAllowed ?? false)
                        <td><a class="btn" href="{{ route('admin.faculty.export', $row['faculty_id']) }}">PDF</a></td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($pdfExportAllowed ?? false) ? 6 : 5 }}" style="text-align:center;">No submitted evaluations match the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h3>Qualitative Feedback</h3>
    @forelse($comments as $comment)
        <div class="comment">
            <b>{{ $comment->response?->subjectMapping?->faculty?->faculty_name ?? 'Faculty' }}</b>
            <span class="muted">
                {{ $comment->response?->subjectMapping?->subject?->subject_code }}
                {{ $comment->response?->subjectMapping?->section?->section_name }}
            </span>
            <div>{{ $comment->text_answer }}</div>
        </div>
    @empty
        <p class="muted">No written comments yet.</p>
    @endforelse
</body>
</html>
