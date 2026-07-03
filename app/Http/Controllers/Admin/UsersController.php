<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __invoke(): View
    {
        if (! Schema::hasTable('users')) {
            return view('admin.users', [
                'users' => new LengthAwarePaginator(collect(), 0, 20),
            ]);
        }

        return view('admin.users', [
            'users' => User::query()
                ->with('department')
                ->orderBy('role')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }
}
