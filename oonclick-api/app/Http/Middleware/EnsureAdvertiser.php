<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdvertiser
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !in_array($request->user()->role, ['advertiser', 'admin'])) {
            abort(403, 'Accès réservé aux annonceurs.');
        }

        return $next($request);
    }
}
