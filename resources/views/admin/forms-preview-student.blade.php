@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/forms.css') }}">
@endpush

@section('content')
    <div class="student-preview-page">
        <section class="student-preview-toolbar">
            <a href="{{ route('admin.forms', ['edit' => $form->id]) }}" class="builder-btn builder-btn-secondary">
                Back to Form Builder
            </a>
            <span class="preview-mode-badge">Preview Mode</span>
        </section>

        <section class="student-preview-shell">
            <div class="student-preview-card student-preview-header">
                <div class="top-accent"></div>
                <div class="student-preview-title-row">
                    <div>
                        <p class="builder-eyebrow">Student View Preview</p>
                        <h1>Faculty Evaluation</h1>
                    </div>
                    <span class="preview-mode-badge">Preview Only</span>
                </div>

                <p class="student-preview-notice">
                    This is only a preview. Answers entered here will not be saved and will not affect reports.
                </p>

                <div class="student-preview-subject">
                    <p>{{ $form->title ?: 'Faculty Evaluation Form' }}</p>
                    <h2>Sample Course: Student Evaluation View</h2>
                    <span>Instructor: Sample Faculty</span>
                </div>

                <div class="student-preview-meta">
                    <div>
                        <span>School Year</span>
                        <strong>{{ $form->school_year ?: 'Not set' }}</strong>
                    </div>
                    <div>
                        <span>Semester</span>
                        <strong>{{ $form->semester ?: 'Not set' }}</strong>
                    </div>
                    <div>
                        <span>Status</span>
                        <strong><span class="builder-badge {{ strtolower($status) }}">{{ $status }}</span></strong>
                    </div>
                    <div>
                        <span>Open Date</span>
                        <strong>{{ $form->open_at ? $form->open_at->format('M d, Y h:i A') : 'Not set' }}</strong>
                    </div>
                    <div>
                        <span>Close Date</span>
                        <strong>{{ $form->close_at ? $form->close_at->format('M d, Y h:i A') : 'Not set' }}</strong>
                    </div>
                </div>

                <p class="required-notice"><span class="required">*</span> Indicates required question</p>
            </div>

            @if($questions->isEmpty())
                <div class="student-preview-card question-section">
                    <div class="question-text">No questions added yet.</div>
                    <p>No questions added yet. Go back to the form builder to add questions.</p>
                </div>
            @else
                <form action="#" method="POST" onsubmit="return false;">
                    @foreach($questions as $q)
                        <div class="student-preview-card question-section">
                            <div class="question-number">Question {{ $loop->iteration }}</div>
                            <div class="question-text">
                                {{ $q->question_text }} @if($q->is_required)<span class="required">*</span>@endif
                            </div>

                            @if($q->category)
                                <div class="question-category">{{ $q->category }}</div>
                            @endif

                            @if($q->question_type === 'rating')
                                @php
                                    $scaleMin = (int) data_get($q->options, 'scale_min', 1);
                                    $scaleMax = (int) data_get($q->options, 'scale_max', 5);
                                @endphp
                                <div class="rating-scale">
                                    <span class="scale-label">Poor</span>
                                    <div class="options">
                                        @for($i = $scaleMin; $i <= $scaleMax; $i++)
                                            <label class="radio-option">
                                                <span>{{ $i }}</span>
                                                <input type="radio" name="preview_rating[{{ $q->id }}]" value="{{ $i }}">
                                            </label>
                                        @endfor
                                    </div>
                                    <span class="scale-label">Excellent</span>
                                </div>
                            @elseif($q->question_type === 'multiple_choice')
                                <div class="preview-choice-list">
                                    @forelse(($q->options ?? []) as $option)
                                        <label class="preview-choice-option">
                                            <input type="radio" name="preview_choice[{{ $q->id }}]" value="{{ $option }}">
                                            <span>{{ $option }}</span>
                                        </label>
                                    @empty
                                        <p class="preview-muted">No answer options were added for this multiple choice question.</p>
                                    @endforelse
                                </div>
                            @else
                                <div class="text-answer">
                                    <textarea class="google-input" placeholder="Your answer" rows="2"></textarea>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="student-preview-actions">
                        <a href="{{ route('admin.forms', ['edit' => $form->id]) }}" class="btn-back">Back to Form Builder</a>
                        <button type="submit" class="btn-submit" disabled>Submit Disabled in Preview Mode</button>
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection
