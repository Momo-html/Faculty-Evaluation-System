@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/security.css') }}">
@endpush

@section('content')
@php
    $activeCategory = $activeCategory ?? 'all';
    $categories = $categories ?? [];
    $currentCategory = collect($categories)->firstWhere('key', $activeCategory) ?? [
        'label' => 'All Activity',
        'description' => 'Every recorded admin action',
        'count' => $summary['total'] ?? 0,
    ];
    $hasAdvancedFilters = request()->filled('module') || request()->filled('action');
    $activeFilterCount = collect(['search', 'user_id', 'module', 'action', 'date_from', 'date_to'])
        ->filter(fn (string $field): bool => request()->filled($field))
        ->count();
@endphp

<div class="audit-page">
    <section class="audit-hero">
        <div>
            <span class="audit-kicker">Admin Activity Center</span>
            <h1>Security & Audit Trail</h1>
            <p>Review administrative activity across settings, reports, forms, and sign-in events.</p>
        </div>
        <div class="audit-current-view" aria-label="Current audit view">
            <span>Current View</span>
            <strong>{{ $currentCategory['label'] }}</strong>
            <small>{{ number_format($logs->total()) }} visible {{ Illuminate\Support\Str::plural('record', $logs->total()) }}</small>
        </div>
    </section>

    <section class="audit-section-heading">
        <div>
            <span>Activity Views</span>
            <h2>{{ $currentCategory['label'] }}</h2>
        </div>
        <p>{{ $currentCategory['description'] }}</p>
    </section>

    <section class="audit-summary-grid" aria-label="Audit activity categories">
        @foreach($categories as $category)
            @php
                $categoryUrl = $category['key'] === 'all'
                    ? $filterRoute
                    : $filterRoute.'?category='.$category['key'];
            @endphp
            <a class="audit-summary-card {{ $activeCategory === $category['key'] ? 'active' : '' }}" href="{{ $categoryUrl }}">
                <span>{{ $category['label'] }}</span>
                <strong>{{ number_format($category['count']) }}</strong>
                <small>{{ $category['description'] }}</small>
            </a>
        @endforeach
    </section>

    <section class="audit-card audit-filter-card">
        <div class="audit-filter-header">
            <div>
                <span>Narrow Results</span>
                <h2>Find a specific activity</h2>
            </div>
            <strong>{{ $activeFilterCount ? $activeFilterCount.' active '.Illuminate\Support\Str::plural('filter', $activeFilterCount) : 'No filters applied' }}</strong>
        </div>

        <form method="GET" action="{{ $filterRoute }}" class="audit-filter-form">
            @if($activeCategory !== 'all')
                <input type="hidden" name="category" value="{{ $activeCategory }}">
            @endif

            <div class="audit-filter-main">
                <label class="audit-search-field">
                    <span>Search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user, activity, area, or description">
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
                <div class="audit-date-range">
                    <label>
                        <span>From</span>
                        <input type="date" name="date_from" value="{{ request('date_from') }}">
                    </label>
                    <label>
                        <span>To</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}">
                    </label>
                </div>
                <div class="audit-filter-actions">
                    <button type="submit" class="btn-primary">Apply</button>
                    <a href="{{ $filterRoute }}" class="btn-secondary">Reset</a>
                </div>
            </div>

            <details class="audit-advanced" {{ $hasAdvancedFilters ? 'open' : '' }}>
                <summary>
                    <span>Advanced Filters</span>
                    <small>Module and action</small>
                </summary>
                <div class="audit-advanced-grid">
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
                </div>
            </details>
        </form>
    </section>

    <section class="audit-card audit-table-card">
        <div class="audit-table-header">
            <div>
                <span>Activity Log</span>
                <h2>{{ $currentCategory['label'] }}</h2>
            </div>
            <strong>{{ number_format($logs->total()) }} {{ Illuminate\Support\Str::plural('record', $logs->total()) }}</strong>
        </div>

        <div class="audit-table-scroll">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Person</th>
                        <th>Activity</th>
                        <th>Area</th>
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
                            $module = $log->module ?: 'General';
                            $description = $log->description ?: 'No description provided.';
                            $signal = strtolower($action.' '.$module.' '.$description);
                            $activityLabel = match (true) {
                                str_contains($signal, 'login_failed') || str_contains($signal, 'failed login') || str_contains($signal, 'invalid') => 'Failed login attempt',
                                str_contains($signal, 'logout') || str_contains($signal, 'signed out') => 'Signed out',
                                str_contains($signal, 'login') || str_contains($signal, 'signed in') => 'Signed in',
                                $action === 'EXPORT' && str_contains($signal, 'department') => 'Exported department PDF',
                                $action === 'EXPORT' => 'Exported PDF report',
                                str_contains($signal, 'branding') && str_contains($signal, 'logo') => 'Updated branding logo',
                                str_contains($signal, 'settings') || str_contains($signal, 'setting') => 'Updated settings',
                                str_contains($signal, 'form') && ($action === 'CREATE' || str_contains($signal, 'created')) => 'Created form item',
                                str_contains($signal, 'form') && ($action === 'DELETE' || str_contains($signal, 'deleted') || str_contains($signal, 'archived')) => 'Removed form item',
                                str_contains($signal, 'form') => 'Updated form item',
                                default => Illuminate\Support\Str::headline(str_replace(['.', '_', '-'], ' ', strtolower($action ?: 'activity'))),
                            };
                            $statusClass = match (true) {
                                str_contains($signal, 'fail') || str_contains($signal, 'denied') || str_contains($signal, 'invalid') => 'danger',
                                str_contains($signal, 'delete') || str_contains($signal, 'archive') || str_contains($signal, 'lock') => 'warning',
                                $action === 'EXPORT' => 'export',
                                default => 'success',
                            };
                            $statusLabel = match ($statusClass) {
                                'danger' => 'Needs review',
                                'warning' => 'Important',
                                'export' => 'Export',
                                default => 'Recorded',
                            };
                            $changeCount = $changeKeys->count();
                            $details = [
                                'dateTime' => $log->created_at?->format('F j, Y g:i A') ?? 'N/A',
                                'user' => $log->user->name ?? 'System',
                                'role' => $log->user->role ?? 'system',
                                'action' => $activityLabel,
                                'module' => $module,
                                'description' => $description,
                                'ipAddress' => $log->ip_address ?: 'N/A',
                                'userAgent' => $log->user_agent ?: 'N/A',
                                'changes' => $changes,
                            ];
                        @endphp
                        <tr>
                            <td class="audit-date">
                                <strong>{{ $log->created_at?->format('M j, Y') ?? 'N/A' }}</strong>
                                <span>{{ $log->created_at?->format('g:i A') ?? 'N/A' }}</span>
                            </td>
                            <td class="audit-person">
                                <strong>{{ $log->user->name ?? 'System' }}</strong>
                                <span>{{ ucfirst($log->user->role ?? 'system') }}</span>
                            </td>
                            <td class="audit-activity">
                                <div>
                                    <span class="audit-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    <strong>{{ $activityLabel }}</strong>
                                </div>
                                <p>{{ $description }}</p>
                            </td>
                            <td>
                                <span class="audit-area">{{ $module }}</span>
                            </td>
                            <td class="audit-row-actions">
                                <span class="audit-change-summary">
                                    {{ $changeCount ? $changeCount.' '.Illuminate\Support\Str::plural('field', $changeCount).' changed' : 'No field changes' }}
                                </span>
                                <button type="button" class="audit-details-button" data-audit-target="audit-log-{{ $log->id }}">
                                    View
                                </button>
                                <script type="application/json" id="audit-log-{{ $log->id }}">@json($details)</script>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="audit-empty-state">
                                    <strong>No audit logs found.</strong>
                                    <span>Try a wider date range or reset the filters.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="audit-pagination">
                {{ $logs->links() }}
            </div>
        @endif
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
            <button type="button" class="audit-modal-close" data-audit-close aria-label="Close details">&times;</button>
        </div>

        <div class="audit-detail-grid">
            <div><span>User</span><strong data-audit-field="user"></strong></div>
            <div><span>Role</span><strong data-audit-field="role"></strong></div>
            <div><span>Activity</span><strong data-audit-field="action"></strong></div>
            <div><span>Area</span><strong data-audit-field="module"></strong></div>
            <div><span>Date & Time</span><strong data-audit-field="dateTime"></strong></div>
            <div><span>IP Address</span><strong data-audit-field="ipAddress"></strong></div>
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
