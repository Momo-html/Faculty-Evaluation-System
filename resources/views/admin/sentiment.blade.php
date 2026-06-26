@extends('layouts.admin')

@section('content')
    <div id="sentiment" class="page-content">
        <h2 style="margin-top:0;">Faculty Performance Analytics</h2>

        @forelse($feedbacks as $facultyName => $subjects)
            <div class="card" style="margin-bottom: 25px; padding: 0; overflow: hidden; border: 1px solid #e0e0e0; border-radius: 8px; background: white;">
                {{-- Instructor Header --}}
                <div style="background-color: #f8f9fa; padding: 16px; border-bottom: 2px solid #f3ab21; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; color: #274c07; font-weight: 700; font-size: 1.1rem;">
                        <i class="fas fa-user-tie" style="margin-right: 8px;"></i>{{ $facultyName }}
                    </h3>
                    <span style="font-size: 0.8rem; color: #666; font-weight: 600; text-transform: uppercase;">
                        Detailed Subject Breakdown
                    </span>
                </div>

                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #ffffff; border-bottom: 1px solid #eee;">
                            <th style="padding: 12px 16px; text-align: left; color: #555; font-size: 0.85rem; width: 35%;">Subject</th>
                            <th style="padding: 12px 16px; text-align: center; color: #555; font-size: 0.85rem;">Responses</th>
                            <th style="padding: 12px 16px; text-align: left; color: #555; font-size: 0.85rem;">Performance Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $s)
                            <tr style="border-bottom: 1px solid #f9f9f9; transition: background 0.2s;" 
                                onmouseover="this.style.backgroundColor='#fafafa'" onmouseout="this.style.backgroundColor='white'">
                                
                                <td style="padding: 14px 16px;">
                                    <div style="font-weight: 600; color: #333;">{{ $s->subject_code }}</div>
                                    <div style="font-size: 0.8rem; color: #777;">{{ $s->subject_name }}</div>
                                </td>

                                <td style="padding: 14px 16px; text-align: center;">
                                    <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        {{ $s->respondent_count }}
                                    </span>
                                </td>

                                <td style="padding: 14px 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        {{-- Progress Bar --}}
                                        <div style="flex-grow: 1; max-width: 180px; background: #eee; height: 8px; border-radius: 4px; overflow: hidden; position: relative;">
                                            <div style="width: {{ ($s->mean_score / 5) * 100 }}%; 
                                                        height: 100%; 
                                                        background: {{ $s->mean_score >= 4 ? '#274c07' : ($s->mean_score >= 3 ? '#f3ab21' : '#c62828') }};
                                                        transition: width 0.5s ease-in-out;">
                                            </div>
                                        </div>

                                        <div style="min-width: 40px;">
                                            <strong style="font-size: 1rem; color: #333;">{{ number_format($s->mean_score, 2) }}</strong>
                                        </div>

                                        <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: #777; font-weight: 600; border-left: 2px solid #ddd; padding-left: 8px; min-width: 100px;">
                                            {{ $s->adjectival_rating }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="card" style="padding: 40px; text-align: center; color: #999;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; color: #eee;"></i>
                <p>No evaluation data available yet.</p>
            </div>
        @endforelse
    </div>
@endsection