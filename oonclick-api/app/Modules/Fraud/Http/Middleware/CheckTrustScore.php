<?php

namespace App\Modules\Fraud\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTrustScore
{
    /**
     * Refuse l'accès à toute route protégée si le trust score de l'utilisateur
     * authentifié est inférieur au seuil configuré.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $minTrustScore = config('oonclick.min_trust_score', 40);

        if ($user->trust_score < $minTrustScore) {
            return response()->json([
                'message'     => 'Accès restreint — score de confiance insuffisant',
                'trust_score' => $user->trust_score,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
