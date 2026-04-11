<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            DB::table('users')
                ->where('id', auth()->id())
                ->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
