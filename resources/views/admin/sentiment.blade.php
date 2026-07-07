@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/performance.css') }}">
@endpush

@php
    $filters = $filters ?? [];
    $filterQuery = collect($filters)->filter(fn ($value) => filled($value))->all();
    $summary = $summary ?? [];
    $highestDepartment = $summary['highest_department_average'] ?? null;
@endphp

@section('content')
<div class="performance-page">
    <section class="performance-hero">
        <div>
            <p class="performance-eyebrow">4-Point Rating Scale</p>
            <h1>Performance Analytics Feed</h1>
            <p>Simple quantitative faculty results, performance labels, and PDF exports.</p>
        </div>
        <div class="performance-hero-actions">
            @if($pdfExportAllowed && filled($filters['department_id'] ?? null))
                <a class="performance-btn performance-btn-secondary" href="{{ route('admin.performance-feed.departments.export-selected-pdf', $filterQuery) }}">Export Department</a>
            @else
                <span class="performance-btn performance-btn-disabled">Select Department</span>
            @endif
            @if($pdfExportAllowed && filled($filters['faculty_id'] ?? null))
                <a class="performance-btn performance-btn-secondary" href="{{ route('admin.performance-feed.faculty.export-selected-pdf', $filterQuery) }}">Export Professor</a>
            @else
                <span class="performance-btn performance-btn-disabled">Select Professor</span>
            @endif
        </div>
    </section>

    <section class="performance-card">
        <form method="GET" action="{{ route('admin.performance-feed.index') }}" class="performance-filter-grid">
            <label>
                <span>Academic Year</span>
                <select name="school_year">
                    <option value="">All</option>
                    @foreach($academicYearOptions as $year)
                        <option value="{{ $year }}" @selected(($filters['school_year'] ?? '') === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Semester</span>
                <select name="semester">
                    <option value="">All</option>
                    @foreach($semesterOptions as $semester)
                        <option value="{{ $semester }}" @selected(($filters['semester'] ?? '') === $semester)>{{ $semester }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Department</span>
                <select name="department_id">
                    <option value="">All</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->department_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Professor</span>
                <select name="faculty_id">
                    <option value="">All</option>
                    @foreach($facultyOptions as $faculty)
                        <option value="{{ $faculty->id }}" @selected((string) ($filters['faculty_id'] ?? '') === (string) $faculty->id)>{{ $faculty->faculty_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Form</span>
                <select name="form_id">
                    <option value="">All</option>
                    @foreach($forms as $form)
                        <option value="{{ $form->id }}" @selected((string) ($filters['form_id'] ?? '') === (string) $form->id)>{{ $form->title }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Subject</span>
                <select name="subject_id">
                    <option value="">All</option>
                    @foreach($subjectOptions as $subject)
                        <option value="{{ $subject->id }}" @selected((string) ($filters['subject_id'] ?? '') === (string) $subject->id)>{{ $subject->subject_code }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Section</span>
                <select name="section_id">
                    <option value="">All</option>
                    @foreach($sectionOptions as $section)
                        <option value="{{ $section->id }}" @selected((string) ($filters['section_id'] ?? '') === (string) $section->id)>{{ $section->section_name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="performance-filter-actions">
                <button type="submit" class="performance-btn performance-btn-primary">Apply</button>
                <a href="{{ route('admin.performance-feed.index') }}" class="performance-btn performance-btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="performance-summary-grid">
        <div class="performance-summary-card"><span>Faculty Evaluated</span><strong>{{ number_format($summary['total_faculty_evaluated'] ?? 0) }}</strong></div>
        <div class="performance-summary-card"><span>Responses</span><strong>{{ number_format($summary['total_evaluation_responses'] ?? 0) }}</strong></div>
        <div class="performance-summary-card"><span>Overall Average</span><strong>{{ number_format($summary['overall_average_rating'] ?? 0, 2) }} / 4.00</strong></div>
        <div class="performance-summary-card"><span>Needs Review</span><strong>{{ number_format($summary['faculty_needing_monitoring'] ?? 0) }}</strong></div>
    </section>

    @if($highestDepartment)
        <section class="performance-note">
            <strong>Highest department:</strong> {{ $highestDepartment['department_name'] }} at {{ number_format($highestDepartment['average_rating'], 2) }} / 4.00
        </section>
    @endif

    <section class="performance-card">
        <div class="performance-section-heading">
            <div>
                <h2>Departments</h2>
                <p>Average ratings are based only on submitted rating answers.</p>
            </div>
        </div>
        <div class="performance-list">
            @forelse($departmentPerformance as $department)
                <article class="performance-item">
                    <div class="performance-item-main">
                        <strong>{{ $department['department_name'] }}</strong>
                        <span>{{ number_format($department['faculty_count']) }} faculty | {{ number_format($department['responses']) }} responses</span>
                    </div>
                    <div class="performance-item-score">
                        <strong>{{ number_format($department['average_rating'], 2) }} / 4.00</strong>
                        <span class="performance-badge {{ $department['classification_class'] }}">{{ $department['classification'] }}</span>
                    </div>
                    <div class="performance-item-action">
                        @if($pdfExportAllowed && $department['department_id'])
                            <a class="performance-btn performance-btn-small performance-btn-secondary" href="{{ route('admin.performance-feed.departments.export-pdf', ['department' => $department['department_id']] + Illuminate\Support\Arr::except($filterQuery, ['department_id'])) }}">PDF</a>
                        @else
                            <span class="performance-muted">No export</span>
                        @endif
                    </div>
                </article>
            @empty
                <p class="performance-empty">No department results found.</p>
            @endforelse
        </div>
    </section>

    <section class="performance-card">
        <div class="performance-section-heading">
            <div>
                <h2>Professors</h2>
                <p>Low response counts should be reviewed with caution.</p>
            </div>
        </div>
        <div class="performance-list">
            @forelse($facultyPerformance as $faculty)
                <article class="performance-item professor-item">
                    <div class="performance-item-main">
                        <strong>{{ $faculty['faculty_name'] }}</strong>
                        <span>{{ $faculty['department_name'] }}</span>
                        <small>{{ $faculty['subjects']->join(', ') ?: 'No subject' }} | {{ $faculty['sections']->join(', ') ?: 'No section' }}</small>
                    </div>
                    <div class="performance-item-score">
                        <strong>{{ number_format($faculty['average_rating'], 2) }} / 4.00</strong>
                        <span class="performance-badge {{ $faculty['classification_class'] }}">{{ $faculty['classification'] }}</span>
                        <span class="reliability-badge {{ $faculty['reliability_class'] }}">{{ $faculty['reliability_class'] === 'reliable' ? 'Reliable' : 'Low responses' }}</span>
                    </div>
                    <div class="performance-item-action">
                        @if($pdfExportAllowed && $faculty['faculty_id'])
                            <a class="performance-btn performance-btn-small performance-btn-secondary" href="{{ route('admin.performance-feed.faculty.export-pdf', ['faculty' => $faculty['faculty_id']] + Illuminate\Support\Arr::except($filterQuery, ['faculty_id'])) }}">PDF</a>
                        @else
                            <span class="performance-muted">No export</span>
                        @endif
                    </div>
                </article>
            @empty
                <p class="performance-empty">No professor results found.</p>
            @endforelse
        </div>
    </section>

    <section class="performance-card">
        <div class="performance-section-heading">
            <div>
                <h2>Scale</h2>
                <p>The system uses a fixed 4-point rating scale.</p>
            </div>
        </div>
        <div class="performance-legend">
            @foreach($performanceLegend as $legend)
                <span class="performance-badge {{ $legend['class'] }}">{{ number_format($legend['min'], 2) }} - {{ number_format($legend['max'], 2) }} {{ $legend['label'] }}</span>
            @endforeach
        </div>
    </section>

    <section class="performance-card">
        <div class="performance-section-heading">
            <div>
                <h2>Comments</h2>
                <p>Manual review only. Comments are not used for performance labels.</p>
            </div>
        </div>
        <div class="performance-comment-list">
            @forelse($comments->take(8) as $comment)
                <article class="performance-comment">
                    <strong>{{ $comment->response?->subjectMapping?->faculty?->faculty_name ?? 'Unassigned Professor' }}</strong>
                    <span>{{ $comment->response?->subjectMapping?->subject?->subject_code ?? 'N/A' }} | {{ $comment->response?->subjectMapping?->section?->section_name ?? 'N/A' }}</span>
                    <p>{{ $comment->text_answer }}</p>
                </article>
            @empty
                <p class="performance-empty">No comments available.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
