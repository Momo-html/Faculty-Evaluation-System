@extends('layouts.admin')

@section('content')
    <div id="dashboard" class="page-content active">
        <!-- Hidden Data Container for Chart.js -->
        <div id="chart-data" data-labels="{{ json_encode($deptData->pluck('code')) }}"
            data-students="{{ json_encode($deptData->pluck('student_count')) }}"
            data-faculty="{{ json_encode($deptData->pluck('faculty_count')) }}"
            data-velocity-labels="{{ json_encode($velocityLabels) }}" data-velocity-data="{{ json_encode($velocityData) }}">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin:0; color: var(--feu-green); font-weight: 700;">Evaluation Analytics</h2>
            <div
                style="background:#e8f5e9; color:#2e7d32; padding:5px 15px; border-radius:20px; font-size:12px; font-weight:600;">
                @if($activeForm)
                    SY: {{ $activeForm->school_year }} | {{ $activeForm->semester }}
                @else
                    No Active Period
                @endif
            </div>
        </div>

        @if($lowParticipation->isNotEmpty())
            <div
                style="background: #eef5ea; border-left: 5px solid var(--feu-green); padding: 15px; margin-bottom: 25px; border-radius: 4px;">
                <strong style="color: var(--feu-green);">Admin Attention Required</strong>
                <p style="margin:0; font-size: 13px; color: #555;">
                    Low participation sections:
                    @foreach($lowParticipation as $low)
                        <b>{{ $low->section_name }}</b> ({{ number_format($low->rate, 0) }}%)@if(!$loop->last), @endif
                    @endforeach
                </p>
            </div>
        @endif

        <!-- TOP STATS -->
        <div class="stats-grid">
            <div class="stat-box">
                <small>Total Students</small>
                <div style="font-size: 26px; font-weight: 800;">{{ $totalPopulation }}</div>
            </div>
            <div class="stat-box">
                <small>Total Faculty</small>
                <div style="font-size: 26px; font-weight: 800;">{{ $totalFaculty }}</div>
            </div>
            <div class="stat-box">
                <small>Participation Rate</small>
                <div style="color:#2e7d32; font-size: 26px; font-weight: 800;">{{ number_format($participationRate, 1) }}%
                </div>
            </div>
            <div class="stat-box">
                <small>Total Responses</small>
                <div style="font-size: 26px; font-weight: 800;">{{ $totalResponses }}</div>
            </div>
        </div>

        <!-- MAIN DISTRIBUTION CHARTS -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 25px 0;">
            <div class="card" style="height: 380px; display: flex; flex-direction: column;">
                <h3 style="margin-top:0;">Student Population</h3>
                <div style="flex-grow: 1; position: relative;">
                    <canvas id="popChart"></canvas>
                </div>
            </div>

            <div class="card" style="height: 380px; display: flex; flex-direction: column;">
                <h3 style="margin-top:0;">Faculty Distribution</h3>
                <div style="flex-grow: 1; position: relative;">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
        </div>

        <!-- NEW: ANALYTICS & PREDICTIVE FORECAST -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 25px 0;">
            <!-- Descriptive: Response Velocity -->
            <div class="card" style="height: 350px;">
                <h3 style="margin-top:0; color: var(--feu-green);">Response Velocity (Last 7 Days)</h3>
                <div style="height: 270px; position: relative;">
                    <canvas id="velocityChart"></canvas>
                </div>
            </div>

            <!-- Predictive: Goal Forecast -->
            <div class="card"
                style="height: 350px; background: var(--feu-green); color: white; border: none; display: flex; flex-direction: column; justify-content: center;">
                <h3 style="margin-top:0; color: var(--feu-gold); text-align: center;">Goal Forecast</h3>
                <div style="text-align: center; margin-top: 20px;">
                    <small style="opacity: 0.8; text-transform: uppercase; font-size: 10px; letter-spacing: 1px;">Daily
                        Momentum</small>
                    <div style="font-size: 32px; font-weight: 800; margin-bottom: 15px;">+{{ $dailyAverage ?? 0 }} <span
                            style="font-size: 14px; font-weight: 400;">avg</span></div>

                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 20px auto; width: 80%;">

                    <small style="opacity: 0.8; text-transform: uppercase; font-size: 10px; letter-spacing: 1px;">Target
                        Reached In</small>
                    <div style="font-size: 52px; font-weight: 800; color: var(--feu-gold);">{{ $daysUntilTarget ?? 'N/A' }}
                    </div>
                    <p style="font-size: 13px; font-weight: 600; margin-top: 5px;">Projected:
                        {{ $projectedDate ?? 'Pending Data' }}</p>
                </div>
            </div>
        </div>

        <!-- FACULTY READINESS TABLE -->
        <div class="card">
            <h3 style="margin-top:0;">Faculty Readiness Status</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; background: #f8f9fa;">
                        <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">Instructor</th>
                        <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">Overall Progress</th>
                        <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">Status</th>
                        <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facultyReadiness as $faculty)
                        <tr>
                            <td style="padding: 12px; border-bottom: 1px solid #eee;">
                                <b style="color: var(--feu-green); font-size: 1.1rem;">{{ $faculty->faculty_name }}</b>
                            </td>
                            <td style="padding: 12px; border-bottom: 1px solid #eee; width: 35%;">
                                <div
                                    style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px;">
                                    <span>{{ $faculty->total_received }} / {{ $faculty->total_expected }}</span>
                                    <span>{{ number_format($faculty->rate, 0) }}%</span>
                                </div>
                                <div style="width:100%; background:#eee; height:8px; border-radius:10px; overflow: hidden;">
                                    <div
                                        style="width:{{ $faculty->rate }}%; background:#2e7d32; height:100%; border-radius:10px;">
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 12px; border-bottom: 1px solid #eee;">
                                <span class="badge {{ $faculty->rate >= 80 ? 'pos' : 'neu' }}">
                                    {{ $faculty->rate >= 80 ? 'READY' : 'INCOMPLETE' }}
                                </span>
                            </td>
                            <td style="padding: 12px; border-bottom: 1px solid #eee;">
                                <a href="{{ route('admin.faculty.export', $faculty->id) }}" class="btn-primary"
                                    style="padding:5px 10px; font-size:11px; text-decoration: none;">
                                    PDF Report
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 20px;">No faculty data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/admin/dashboard.js') }}"></script>
@endpush
