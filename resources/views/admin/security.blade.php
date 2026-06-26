@extends('layouts.admin')

@section('content')
<div class="main-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin:0; color: var(--feu-green); font-weight: 700;">Security & Audit Trail</h2>
        <div style="display: flex; gap: 10px;">
            <button class="btn-primary" style="font-size: 12px; background: #666;">Clear Old Logs</button>
            <button class="btn-primary" style="font-size: 12px;">Export Audit CSV</button>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden; border-top: 4px solid var(--feu-gold);">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; background: white;">
            <thead>
                <tr style="background: #fdfdfd; text-align: left; border-bottom: 1px solid #eee;">
                    <th style="padding: 15px; color: var(--feu-green); width: 180px;">TIMESTAMP</th>
                    <th style="padding: 15px; color: var(--feu-green);">ADMIN</th>
                    <th style="padding: 15px; color: var(--feu-green);">ACTION</th>
                    <th style="padding: 15px; color: var(--feu-green);">RESOURCE</th>
                    <th style="padding: 15px; color: var(--feu-green);">DESCRIPTION</th>
                    <th style="padding: 15px; color: var(--feu-green); text-align: center;">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr style="border-bottom: 1px solid #f9f9f9;">
                        <td style="padding: 15px; color: #888; font-family: monospace;">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td style="padding: 15px; font-weight: 600;">
                            {{ $log->user->name ?? 'System' }}
                        </td>
                        <td style="padding: 15px;">
                            <span class="badge {{ in_array($log->action, ['DELETE', 'FAIL']) ? 'neg' : 'pos' }}" style="font-size: 10px; letter-spacing: 0.5px;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="padding: 15px; color: #555;">{{ $log->resource }}</td>
                        <td style="padding: 15px; color: #444;">{{ $log->description }}</td>
                        <td style="padding: 15px; text-align: center; color: #aaa; font-size: 11px;">
                            {{ $log->ip_address }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #999;">
                            No security incidents or logs recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection