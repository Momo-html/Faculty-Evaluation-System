@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/security.css') }}">
@endpush

@section('content')
<div class="audit-page">
    <section class="audit-hero">
        <div>
            <h1>Security & Audit Trail</h1>
            <p>Audit logs are view-only and record administrative activity across the portal.</p>
        </div>
    </section>

    <section class="audit-summary-grid" aria-label="Audit summary">
        <div class="audit-summary-card">
            <span>Total Logs</span>
            <strong>{{ number_format($summary['total'] ?? 0) }}</strong>
        </div>
        <div class="audit-summary-card">
            <span>Settings Changes</span>
            <strong>{{ number_format($summary['settings'] ?? 0) }}</strong>
        </div>
        <div class="audit-summary-card">
            <span>PDF Exports</span>
            <strong>{{ number_format($summary['pdf'] ?? 0) }}</strong>
        </div>
        <div class="audit-summary-card">
            <span>Form Builder Actions</span>
            <strong>{{ number_format($summary['forms'] ?? 0) }}</strong>
        </div>
        <div class="audit-summary-card">
            <span>Login/Security Events</span>
            <strong>{{ number_format($summary['security'] ?? 0) }}</strong>
        </div>
    </section>

    <section class="audit-card">
        <form method="GET" action="{{ $filterRoute }}" class="audit-filter-grid">
            <label>
                <span>Search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="User, action, module, description">
            </label>
            <label>
                <span>User</span>
                <select name="user_id">
                    <option value="">All users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                            {{ $user->name }} - {{ $user->role }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Module</span>
                <select name="module">
                    <option value="">All modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Action</span>
                <select name="action">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Date From</span>
                <input type="date" name="date_from" value="{{ request('date_from') }}">
            </label>
            <label>
                <span>Date To</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}">
            </label>
            <div class="audit-filter-actions">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ $filterRoute }}" class="btn-secondary">Reset Filters</a>
            </div>
        </form>
    </section>

    <section class="audit-card audit-table-card">
        <div class="audit-table-scroll">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $oldValues = is_array($log->old_values) ? $log->old_values : [];
                            $newValues = is_array($log->new_values) ? $log->new_values : [];
                            $changeKeys = collect(array_keys(array_merge($oldValues, $newValues)))->unique()->values();
                            $changes = $changeKeys->map(fn ($key) => [
                                'field' => Illuminate\Support\Str::headline($key),
                                'old' => $oldValues[$key] ?? null,
                                'new' => $newValues[$key] ?? null,
                            ])->all();
                            $action = strtoupper((string) $log->action);
                            $actionClass = match (true) {
                                str_contains($action, 'DELETE') || str_contains($action, 'FAIL') => 'danger',
                                $action === 'EXPORT' => 'export',
                                str_contains($action, 'LOGIN') => 'login',
                                str_contains($action, 'SETTING') || str_contains((string) $log->module, 'Settings') => 'settings',
                                $action === 'UPDATE' => 'update',
                                $action === 'CREATE' => 'create',
                                default => 'neutral',
                            };
                            $details = [
                                'dateTime' => $log->created_at?->format('F j, Y g:i A') ?? 'N/A',
                                'user' => $log->user->name ?? 'System',
                                'role' => $log->user->role ?? 'system',
                                'action' => $action,
                                'module' => $log->module ?? 'N/A',
                                'description' => $log->description ?? 'No description provided.',
                                'userAgent' => $log->user_agent ?: 'N/A',
                                'changes' => $changes,
                            ];
                        @endphp
                        <tr>
                            <td class="audit-date">{{ $log->created_at?->format('Y-m-d') }}<span>{{ $log->created_at?->format('g:i A') }}</span></td>
                            <td><strong>{{ $log->user->name ?? 'System' }}</strong></td>
                            <td><span class="audit-role">{{ $log->user->role ?? 'system' }}</span></td>
                            <td><span class="audit-badge {{ $actionClass }}">{{ $action }}</span></td>
                            <td>{{ $log->module ?? 'N/A' }}</td>
                            <td class="audit-description">{{ $log->description ?? 'No description provided.' }}</td>
                            <td>
                                <span class="audit-change-summary">
                                    {{ $changeKeys->count() ? $changeKeys->count().' '.Illuminate\Support\Str::plural('field', $changeKeys->count()).' changed' : 'No value changes' }}
                                </span>
                                <button type="button" class="audit-details-button" data-audit-target="audit-log-{{ $log->id }}">
                                    View Details
                                </button>
                                <script type="application/json" id="audit-log-{{ $log->id }}">@json($details)</script>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="audit-empty-state">
                                    <strong>No audit logs found.</strong>
                                    <span>Try adjusting your filters or check again after system activity occurs.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="audit-modal" id="auditDetailsModal" hidden>
    <div class="audit-modal-backdrop" data-audit-close></div>
    <section class="audit-modal-panel" role="dialog" aria-modal="true" aria-labelledby="auditModalTitle">
        <div class="audit-modal-header">
            <div>
                <h2 id="auditModalTitle">Audit Log Details</h2>
                <p id="auditModalSubtitle"></p>
            </div>
            <button type="button" class="audit-modal-close" data-audit-close aria-label="Close details">×</button>
        </div>

        <div class="audit-detail-grid">
            <div><span>User</span><strong data-audit-field="user"></strong></div>
            <div><span>Role</span><strong data-audit-field="role"></strong></div>
            <div><span>Action</span><strong data-audit-field="action"></strong></div>
            <div><span>Module</span><strong data-audit-field="module"></strong></div>
            <div><span>Date & Time</span><strong data-audit-field="dateTime"></strong></div>
        </div>

        <div class="audit-detail-section">
            <span>Description</span>
            <p data-audit-field="description"></p>
        </div>

        <div class="audit-detail-section">
            <span>User Agent</span>
            <p class="audit-user-agent" data-audit-field="userAgent"></p>
        </div>

        <div class="audit-detail-section">
            <span>Changed Values</span>
            <div class="audit-changes" id="auditChangesList"></div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/security.js') }}"></script>
@endpush
