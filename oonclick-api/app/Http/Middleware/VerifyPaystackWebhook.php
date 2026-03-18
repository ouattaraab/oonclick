<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPaystackWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('x-paystack-signature');
        $secret    = config('oonclick.paystack.webhook_secret');

        if (!$signature || !$secret) {
            return response()->json(['message' => 'Webhook non autorisé'], 401);
        }

        $expected = hash_hmac('sha512', $request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Signature invalide'], 401);
        }

        return $next($request);
    }
}
