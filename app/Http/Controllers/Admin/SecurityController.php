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
            ]);
        }

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
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.security', [
            'logs' => $logs,
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'modules' => ActivityLog::query()->select('module')->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
        ]);
    }
}
