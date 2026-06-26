@extends('layouts.user')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/evaluation-form.css') }}">
@endpush

@section('content')
    <div class="form-container">
        {{-- Header Card --}}
        <div class="form-card header-card">
            <div class="top-accent"></div>
            <h1>Faculty Evaluation</h1>

            <!-- Focus on the specific subject being evaluated -->
            <div style="margin-top: 15px;">
                <h2 style="margin: 0; color: #202124; font-size: 22px;">{{ $evaluation->subject_code }}:
                    {{ $evaluation->subject_name }}</h2>
                <p style="font-size: 16px; color: #5f6368; margin: 5px 0;">
                    <strong>Instructor:</strong> {{ $evaluation->faculty_name }}
                </p>
            </div>

            <hr style="border: 0; border-top: 1px solid #dadce0; margin: 15px 0;">
            <p class="required-notice"><span class="required">*</span> Indicates required question</p>
        </div>

        <form action="{{ route('eval.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="mapping_id" value="{{ $evaluation->mapping_id }}">

            @foreach($questions as $q)
                <div class="form-card question-section">
                    <div class="question-text">
                        {{ $q->question_text }} <span class="required">*</span>
                    </div>

                    @if(strtolower($q->type) == 'scale')
                        <div class="rating-scale">
                            <span class="scale-label">Poor</span>
                            <div class="options">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="radio-option">
                                        <span>{{ $i }}</span>
                                        <input type="radio" name="rating[{{ $q->id }}]" value="{{ $i }}" required>
                                    </label>
                                @endfor
                            </div>
                            <span class="scale-label">Excellent</span>
                        </div>
                    @elseif(strtolower($q->type) == 'text')
                        <div class="text-answer">
                            <textarea name="comments[{{ $q->id }}]" class="google-input" placeholder="Your answer" rows="1"
                                oninput='this.style.height = "";this.style.height = this.scrollHeight + "px"'></textarea>
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
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/user/evaluation-form.js') }}"></script>
@endpush
