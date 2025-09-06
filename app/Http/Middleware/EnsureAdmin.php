<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (! $u || $u->role !== 'admin' || ! $u->is_active) {
            abort(403);
        }
        return $next($request);
    }
}
