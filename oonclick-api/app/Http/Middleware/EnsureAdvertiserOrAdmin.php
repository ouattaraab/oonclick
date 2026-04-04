<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdvertiserOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['advertiser', 'admin'])) {
            return response()->json(['message' => 'Accès réservé aux annonceurs et administrateurs'], 403);
        }

        return $next($request);
    }
}
