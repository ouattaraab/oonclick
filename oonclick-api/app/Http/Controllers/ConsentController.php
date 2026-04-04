<?php

namespace App\Http\Controllers;

use App\Models\UserConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consent management API for mobile clients.
 *
 * GET  /api/consents   — list the authenticated user's consents
 * POST /api/consents   — update a single consent (C5 or C6 only)
 *
 * C1–C4 are mandatory and cannot be revoked via this endpoint.
 * They are recorded at registration time in the Auth flow.
 */
class ConsentController extends Controller
{
    /**
     * Return all consent records for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $consents = UserConsent::where('user_id', $request->user()->id)
            ->orderBy('consent_type')
            ->get()
            ->map(fn ($c) => [
                'consent_type' => $c->consent_type,
                'granted'      => $c->granted,
                'granted_at'   => $c->granted_at?->toIso8601String(),
                'revoked_at'   => $c->revoked_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $consents]);
    }

    /**
     * Update a single optional consent (C5 or C6).
     *
     * Mandatory consents C1–C4 cannot be revoked via this endpoint.
     * Attempting to revoke them returns HTTP 422.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'consent_type' => ['required', 'string', 'in:C1,C2,C3,C4,C5,C6'],
            'granted'      => ['required', 'boolean'],
        ]);

        $type    = $data['consent_type'];
        $granted = $data['granted'];

        // C1–C4 are irrevocable — return a clear error if the client tries.
        if (in_array($type, ['C1', 'C2', 'C3', 'C4'], true) && ! $granted) {
            return response()->json([
                'message' => 'Les consentements obligatoires (C1–C4) ne peuvent pas être révoqués.',
            ], 422);
        }

        UserConsent::record(
            $request->user()->id,
            $type,
            $granted,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json([
            'message'      => 'Consentement mis à jour.',
            'consent_type' => $type,
            'granted'      => $granted,
        ]);
    }
}
