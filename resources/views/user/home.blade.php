@extends('layouts.user')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/home.css') }}">
@endpush

@section('content')
    <div class="container">
        <!-- 1. Data Privacy Notice (Initial State) -->
        <div id="privacy-notice" class="notice-wrapper">
            <div class="card privacy-card">
                <div class="privacy-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Data Privacy Notice</h3>
                <p>Welcome, Tamaraw! Please note that all evaluations are <strong>strictly anonymous</strong>. Your feedback
                    is used solely for faculty development in compliance with the Data Privacy Act.</p>
                <button id="accept-privacy-btn" class="btn-feu" onclick="acceptPrivacy()">I Accept & Proceed</button>
            </div>
        </div>

        <!-- 2. Dashboard Content (Hidden until privacy accepted) -->
        <div id="main-content" style="display: none;">
            <h2 class="section-title">Available Evaluations</h2>

            <div class="subject-grid">
                @forelse($availableEvaluations as $item)
                    <div class="card subject-card">
                        <div class="subject-info">
                            <!-- Highlight the Subject Code and Name as the main title -->
                            <h3 style="color: var(--feu-green); margin:0; font-size: 1.1rem;">
                                {{ $item->subject_code }}: {{ $item->subject_name }}
                            </h3>

                            <div style="margin-top: 10px;">
                                <span style="color: #666; font-size: 0.9rem;">Instructor:</span>
                                <p style="margin: 0; font-weight: bold;">{{ $item->faculty_name }}</p>
                            </div>
                        </div>

                        <div class="action-area">
                            @if(in_array($item->mapping_id, $completedEvaluations))
                                <button class="btn-start-eval" style="background: #ccc; cursor: not-allowed;" disabled>
                                    Completed <i class="fas fa-check-circle"></i>
                                </button>
                            @else
                                <a href="{{ route('eval.show', $item->mapping_id) }}" class="btn-start-eval">
                                    Start Evaluation
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <!-- Evaluation Closed Card -->
                    <div class="card closed-card">
                        <div class="closed-icon"><i class="fas fa-calendar-times"></i></div>
                        <h3>Evaluation Period Closed</h3>
                        <p>There are no active faculty evaluations available at this time.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/user/home.js') }}"></script>
@endpush