<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdvertiser
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role !== 'advertiser') {
            return response()->json(['message' => 'Accès réservé aux annonceurs'], 403);
        }

        return $next($request);
    }
}
