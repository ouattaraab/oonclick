<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    // =========================================================================
    // POST /api/fcm/register
    // =========================================================================

    /**
     * Register or update an FCM device token for the authenticated user.
     *
     * Creates a new row if the token is new, or updates the existing one
     * (re-activates it and refreshes last_used_at).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'       => ['required', 'string'],
            'device_type' => ['required', 'string', 'in:android,ios,web'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        FcmToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token'   => $validated['token'],
            ],
            [
                'device_type'  => $validated['device_type'],
                'device_name'  => $validated['device_name'] ?? null,
                'is_active'    => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['message' => 'Token enregistré.'], 200);
    }

    // =========================================================================
    // POST /api/fcm/unregister
    // =========================================================================

    /**
     * Remove an FCM device token for the authenticated user (e.g., on logout).
     *
     * Silently succeeds even if the token does not exist.
     */
    public function unregister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        FcmToken::where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => 'Token supprimé.'], 200);
    }
}
