@extends('layouts.user')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/evaluation-form.css') }}">
@endpush

@section('content')
    <div class="form-container">
        {{-- Header Card --}}
        <div class="form-card header-card">
            <div class="top-accent"></div>
            <h1>{{ $portalSettings['student_evaluation_page_title'] ?? 'Faculty Evaluation' }}</h1>
            @if(!empty($portalSettings['student_evaluation_instructions']))
                <p style="margin: 0 0 12px; color: #5f6368;">{{ $portalSettings['student_evaluation_instructions'] }}</p>
            @endif

            <!-- Focus on the specific subject being evaluated -->
            <div style="margin-top: 15px;">
                @if($form)
                    <p style="margin: 0 0 8px; color: var(--feu-green); font-weight: 700;">
                        {{ $form->title }} | {{ $form->school_year }} | {{ $form->semester }}
                    </p>
                @endif
                <h2 style="margin: 0; color: #202124; font-size: 22px;">{{ $evaluation->subject_code }}:
                    {{ $evaluation->subject_name }}</h2>
                <p style="font-size: 16px; color: #5f6368; margin: 5px 0;">
                    <strong>Instructor:</strong> {{ $evaluation->faculty_name }}
                </p>
            </div>

            <hr style="border: 0; border-top: 1px solid #dadce0; margin: 15px 0;">
            @if(($portalSettings['show_deadline_to_students'] ?? '1') === '1' && $form?->close_at)
                <p style="margin: 10px 0 0; color: #5f6368;"><strong>Deadline:</strong> {{ $form->close_at->format('F d, Y h:i A') }}</p>
            @endif
            @if(($portalSettings['show_required_question_indicator'] ?? '1') === '1')
                <p class="required-notice"><span class="required">*</span> Indicates required question</p>
            @endif
        </div>

        @if($questions->isEmpty())
            <div class="form-card question-section">
                <div class="question-text">No active evaluation questions are available right now.</div>
                <p>Please return to your dashboard and try again later.</p>
            </div>
        @else
        <form action="{{ route('eval.submit') }}" method="POST" data-confirm-submit="{{ ($portalSettings['show_confirmation_before_submit'] ?? '1') === '1' ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="form_id" value="{{ $evaluation->form_id }}">
            <input type="hidden" name="mapping_id" value="{{ $evaluation->mapping_id }}">

            @foreach($questions as $q)
                <div class="form-card question-section">
                    <div class="question-text">
                        {{ $q->question_text }} @if($q->is_required && (($portalSettings['show_required_question_indicator'] ?? '1') === '1'))<span class="required">*</span>@endif
                    </div>

                    @if(in_array(strtolower($q->question_type ?? $q->type ?? ''), ['scale', 'rating'], true))
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
                                        <input type="radio" name="rating[{{ $q->id }}]" value="{{ $i }}" @required($q->is_required)>
                                    </label>
                                @endfor
                            </div>
                            <span class="scale-label">Excellent</span>
                        </div>
                    @elseif(strtolower($q->question_type ?? $q->type ?? '') === 'multiple_choice')
                        <div class="text-answer">
                            <select name="choice[{{ $q->id }}]" class="google-input" @required($q->is_required)>
                                <option value="">Choose an answer</option>
                                @foreach(($q->options ?? []) as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="text-answer">
                            <textarea name="comments[{{ $q->id }}]" class="google-input" placeholder="Your answer" rows="1"
                                oninput='this.style.height = "";this.style.height = this.scrollHeight + "px"' @required($q->is_required)></textarea>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="form-actions"
                style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                <a href="{{ route('user.index') }}" class="btn-back" style="text-decoration: none; color: var(--feu-green);">
                    Clear form
                </a>
                <button type="submit" class="btn-submit" id="submitBtn">
                    Submit
                </button>
            </div>
        </form>
        @endif
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/user/evaluation-form.js') }}"></script>
@endpush
