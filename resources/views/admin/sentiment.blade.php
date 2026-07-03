@extends('layouts.admin')

@section('content')
    <div id="sentiment" class="page-content">
        <h2 style="margin-top:0;">Evaluation Results and Sentiment</h2>

        @forelse($feedbacks as $facultyName => $answers)
            <div class="card" style="margin-bottom: 25px; padding: 0; overflow: hidden; border: 1px solid #e0e0e0; border-radius: 8px; background: white;">
                <div style="background-color: #f8f9fa; padding: 16px; border-bottom: 2px solid #f3ab21; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; color: #274c07; font-weight: 700; font-size: 1.1rem;">{{ $facultyName }}</h3>
                    <span style="font-size: 0.8rem; color: #666; font-weight: 600; text-transform: uppercase;">
                        Written Feedback
                    </span>
                </div>

                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #ffffff; border-bottom: 1px solid #eee;">
                            <th style="padding: 12px 16px; text-align: left;">Subject</th>
                            <th style="padding: 12px 16px; text-align: left;">Comment</th>
                            <th style="padding: 12px 16px; text-align: center;">Sentiment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($answers as $answer)
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding: 14px 16px;">
                                    <div style="font-weight: 600; color: #333;">{{ $answer->subject_code }}</div>
                                    <div style="font-size: 0.8rem; color: #777;">{{ $answer->subject_name }}</div>
                                </td>
                                <td style="padding: 14px 16px;">{{ $answer->comment }}</td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <span class="badge {{ $answer->sentiment === 'Positive' ? 'pos' : ($answer->sentiment === 'Negative' ? 'neg' : 'neu') }}">
                                        {{ $answer->sentiment }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="card" style="padding: 40px; text-align: center; color: #999;">
                <p>No written feedback has been submitted yet.</p>
            </div>
        @endforelse
    </div>
@endsection
