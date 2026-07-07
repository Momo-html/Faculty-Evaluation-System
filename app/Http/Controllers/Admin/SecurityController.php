<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __invoke(Request $request): View
    {
        if (! Schema::hasTable('activity_logs')) {
            return view('admin.security', [
                'logs' => new LengthAwarePaginator(collect(), 0, 15),
                'actions' => collect(),
                'modules' => collect(),
                'users' => collect(),
                'summary' => $this->emptySummary(),
                'filterRoute' => $this->filterRoute($request),
            ]);
        }

        $baseQuery = ActivityLog::query();
        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search');
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('action'), fn (Builder $query) => $query->where('action', $request->string('action')))
            ->when($request->filled('module'), fn (Builder $query) => $query->where('module', $request->string('module')))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.security', [
            'logs' => $logs,
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'modules' => ActivityLog::query()->select('module')->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'users' => ActivityLog::query()->with('user')->whereNotNull('user_id')->get()->pluck('user')->filter()->unique('id')->sortBy('name')->values(),
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'settings' => (clone $baseQuery)->where('module', 'like', '%Settings%')->count(),
                'pdf' => (clone $baseQuery)->where(function (Builder $query): void {
                    $query->where('action', 'EXPORT')->orWhere('module', 'like', '%PDF%')->orWhere('module', 'like', '%Report%');
                })->count(),
                'forms' => (clone $baseQuery)->where('module', 'like', '%Form%')->count(),
                'security' => (clone $baseQuery)->where(function (Builder $query): void {
                    $query->where('action', 'like', '%LOGIN%')->orWhere('module', 'like', '%Security%');
                })->count(),
            ],
            'filterRoute' => $this->filterRoute($request),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'settings' => 0,
            'pdf' => 0,
            'forms' => 0,
            'security' => 0,
        ];
    }

    private function filterRoute(Request $request): string
    {
        if ($request->routeIs('superadmin.*')) {
            return route('superadmin.audit-logs.index');
        }

        return route('admin.security');
    }
}
