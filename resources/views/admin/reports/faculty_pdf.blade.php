@extends('layouts.admin')

@section('content')
<div class="page-content">
    <div class="page-heading">
        <div>
            <h2 style="color: var(--feu-green);">Evaluation Reports</h2>
            <p class="table-muted-text">Filter submitted evaluations and export printable PDF reports.</p>
        </div>
    </div>

    @unless($pdfExportAllowed ?? false)
        <div class="alert-danger">PDF export is currently disabled by system settings.</div>
    @endunless

    <div class="card" style="border-top: 4px solid var(--feu-gold);">
        <form method="GET" action="{{ route('admin.reports.faculty-pdf') }}" class="expansion-grid">
            <div class="input-group">
                <label>Evaluation Form</label>
                <select name="form_id">
                    <option value="">All Forms</option>
                    @foreach($forms as $form)
                        <option value="{{ $form->id }}" @selected(($filters['form_id'] ?? '') == $form->id)>{{ $form->title }} - {{ $form->school_year }} {{ $form->semester }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label>Department</label>
                <select name="department_id" id="departmentFilter">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->department_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label>Faculty / Professor</label>
                <select name="faculty_id" id="facultyFilter">
                    <option value="">All Faculty</option>
                    @foreach($facultyOptions as $faculty)
                        <option value="{{ $faculty->id }}" @selected(($filters['faculty_id'] ?? '') == $faculty->id)>{{ $faculty->faculty_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label>Subject / Course</label>
                <select name="subject_id">
                    <option value="">All Subjects</option>
                    @foreach($subjectOptions as $subject)
                        <option value="{{ $subject->id }}" @selected(($filters['subject_id'] ?? '') == $subject->id)>{{ $subject->subject_code }} - {{ $subject->subject_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label>Section</label>
                <select name="section_id">
                    <option value="">All Sections</option>
                    @foreach($sectionOptions as $section)
                        <option value="{{ $section->id }}" @selected(($filters['section_id'] ?? '') == $section->id)>{{ $section->section_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label>Academic Year</label>
                <input type="text" name="school_year" value="{{ $filters['school_year'] ?? '' }}" placeholder="2026-2027">
            </div>
            <div class="input-group">
                <label>Semester</label>
                <select name="semester">
                    <option value="">All Semesters</option>
                    @foreach(['1st Semester', '2nd Semester', 'Summer'] as $semester)
                        <option value="{{ $semester }}" @selected(($filters['semester'] ?? '') === $semester)>{{ $semester }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label>Date From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="input-group">
                <label>Date To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="input-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn-primary">Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="card" style="border-top: 4px solid var(--feu-gold);">
        <div class="card-header-row">
            <div>
                <h3>PDF Export Options</h3>
                <p class="table-muted-text">Exports use the selected filters above.</p>
            </div>
            <div class="page-actions">
                <a class="btn-secondary {{ ($pdfExportAllowed ?? false) ? '' : 'disabled' }}"
                    href="{{ ($pdfExportAllowed ?? false) && ($filters['department_id'] ?? null) ? route('admin.reports.department.pdf', request()->query()) : '#' }}"
                    onclick="if(!{{ ($pdfExportAllowed ?? false) ? 'true' : 'false' }}){alert('PDF export is currently disabled by system settings.'); return false;} if(!document.getElementById('departmentFilter').value){alert('Please select a department before exporting a department report.'); return false;}">
                    Export Department PDF
                </a>
                <a class="btn-secondary {{ ($pdfExportAllowed ?? false) ? '' : 'disabled' }}"
                    href="{{ ($pdfExportAllowed ?? false) && ($filters['faculty_id'] ?? null) ? route('admin.reports.faculty.pdf', request()->query()) : '#' }}"
                    onclick="if(!{{ ($pdfExportAllowed ?? false) ? 'true' : 'false' }}){alert('PDF export is currently disabled by system settings.'); return false;} if(!document.getElementById('facultyFilter').value){alert('Please select a professor before exporting an individual report.'); return false;}">
                    Export Individual Faculty PDF
                </a>
            </div>
        </div>

        <table>
            <tr>
                <td><b>Total Submitted Evaluations</b><br><span style="font-size:24px; color:var(--feu-green); font-weight:800;">{{ $totalRespondents }}</span></td>
                <td><b>Overall Average Rating</b><br><span style="font-size:24px; color:var(--feu-green); font-weight:800;">{{ number_format($overallAverage, 2) }}</span></td>
            </tr>
        </table>
    </div>

    <div class="card" style="border-top: 4px solid var(--feu-gold);">
        <h3 style="color: var(--feu-green); margin-top:0;">Faculty Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th>Respondents</th>
                    <th>Average Score</th>
                    <th>Subjects</th>
                    <th>Sections</th>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;">No submitted evaluations match the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" style="border-top: 4px solid var(--feu-gold);">
        <h3 style="color: var(--feu-green); margin-top:0;">Qualitative Feedback</h3>
        @forelse($comments as $comment)
            <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #edf0e8;">
                <strong>{{ $comment->response?->subjectMapping?->faculty?->faculty_name ?? 'Faculty' }}</strong>
                <span class="table-muted-text">{{ $comment->response?->subjectMapping?->subject?->subject_code }} {{ $comment->response?->subjectMapping?->section?->section_name }}</span>
                <div>{{ $comment->text_answer }}</div>
            </div>
        @empty
            <p class="table-muted-text">No written comments yet.</p>
        @endforelse
    </div>
</div>
@endsection
