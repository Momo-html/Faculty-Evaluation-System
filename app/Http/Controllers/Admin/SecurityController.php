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
        $activeCategory = $this->activeCategory($request);

        if (! Schema::hasTable('activity_logs')) {
            $summary = $this->emptySummary();

            return view('admin.security', [
                'logs' => new LengthAwarePaginator(collect(), 0, 15),
                'actions' => collect(),
                'modules' => collect(),
                'users' => collect(),
                'summary' => $summary,
                'categories' => $this->categories($summary),
                'activeCategory' => $activeCategory,
                'filterRoute' => $this->filterRoute($request),
            ]);
        }

        $baseQuery = ActivityLog::query();
        $summary = $this->summary($baseQuery);
        $logsQuery = ActivityLog::query()
            ->with('user')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = (string) $request->string('search');
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('action'), fn (Builder $query) => $query->where('action', (string) $request->string('action')))
            ->when($request->filled('module'), fn (Builder $query) => $query->where('module', (string) $request->string('module')))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('date_to')));

        $this->applyCategory($logsQuery, $activeCategory);

        $logs = $logsQuery
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.security', [
            'logs' => $logs,
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'modules' => ActivityLog::query()->select('module')->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'users' => ActivityLog::query()->with('user')->whereNotNull('user_id')->get()->pluck('user')->filter()->unique('id')->sortBy('name')->values(),
            'summary' => $summary,
            'categories' => $this->categories($summary),
            'activeCategory' => $activeCategory,
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

    /**
     * @return array<string, int>
     */
    private function summary(Builder $baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'settings' => $this->applyCategory(clone $baseQuery, 'settings')->count(),
            'pdf' => $this->applyCategory(clone $baseQuery, 'reports')->count(),
            'forms' => $this->applyCategory(clone $baseQuery, 'forms')->count(),
            'security' => $this->applyCategory(clone $baseQuery, 'security')->count(),
        ];
    }

    /**
     * @param  array<string, int>  $summary
     * @return array<int, array{key: string, label: string, description: string, count: int}>
     */
    private function categories(array $summary): array
    {
        return [
            [
                'key' => 'all',
                'label' => 'All Activity',
                'description' => 'Every recorded admin action',
                'count' => $summary['total'] ?? 0,
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'description' => 'Portal setup, branding, and report settings',
                'count' => $summary['settings'] ?? 0,
            ],
            [
                'key' => 'reports',
                'label' => 'PDF Reports',
                'description' => 'Individual and department PDF exports',
                'count' => $summary['pdf'] ?? 0,
            ],
            [
                'key' => 'forms',
                'label' => 'Form Builder',
                'description' => 'Evaluation forms, categories, and questions',
                'count' => $summary['forms'] ?? 0,
            ],
            [
                'key' => 'security',
                'label' => 'Login & Security',
                'description' => 'Sign-ins, sign-outs, and access events',
                'count' => $summary['security'] ?? 0,
            ],
        ];
    }

    private function activeCategory(Request $request): string
    {
        $category = $request->query('category', 'all');
        $category = is_string($category) ? $category : 'all';

        if ($category === 'pdf') {
            return 'reports';
        }

        return in_array($category, ['all', 'settings', 'reports', 'forms', 'security'], true)
            ? $category
            : 'all';
    }

    private function applyCategory(Builder $query, string $category): Builder
    {
        return match ($category) {
            'settings' => $query->where(function (Builder $categoryQuery): void {
                $categoryQuery
                    ->where('module', 'like', '%Settings%')
                    ->orWhere('module', 'like', '%Branding%')
                    ->orWhere('action', 'like', '%SETTING%')
                    ->orWhere('description', 'like', '%setting%');
            }),
            'reports' => $query->where(function (Builder $categoryQuery): void {
                $categoryQuery
                    ->where('action', 'EXPORT')
                    ->orWhere('action', 'like', '%PDF%')
                    ->orWhere('module', 'like', '%PDF%')
                    ->orWhere('module', 'like', '%Report%')
                    ->orWhere('description', 'like', '%PDF%');
            }),
            'forms' => $query->where(function (Builder $categoryQuery): void {
                $categoryQuery
                    ->where('module', 'like', '%Form%')
                    ->orWhere('module', 'like', '%Question%')
                    ->orWhere('description', 'like', '%form%')
                    ->orWhere('description', 'like', '%question%');
            }),
            'security' => $query->where(function (Builder $categoryQuery): void {
                $categoryQuery
                    ->where('action', 'like', '%AUTH%')
                    ->orWhere('action', 'like', '%LOGIN%')
                    ->orWhere('action', 'like', '%LOGOUT%')
                    ->orWhere('module', 'like', '%Auth%')
                    ->orWhere('module', 'like', '%Security%')
                    ->orWhere('description', 'like', '%access%')
                    ->orWhere('description', 'like', '%login%')
                    ->orWhere('description', 'like', '%logout%');
            }),
            default => $query,
        };
    }

    private function filterRoute(Request $request): string
    {
        if ($request->routeIs('superadmin.*')) {
            return route('superadmin.audit-logs.index');
        }

        return route('admin.security');
    }
}
