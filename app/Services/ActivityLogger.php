<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(Request $request, string $action, string $description): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => strtoupper($action),
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
