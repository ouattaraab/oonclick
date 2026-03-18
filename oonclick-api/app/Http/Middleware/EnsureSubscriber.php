<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSubscriber
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role !== 'subscriber') {
            return response()->json(['message' => 'Accès réservé aux abonnés'], 403);
        }

        return $next($request);
    }
}
