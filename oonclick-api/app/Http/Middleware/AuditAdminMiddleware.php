<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (in_array($request->method(), $mutatingMethods, true)) {
            $ids = AuditService::fromRequest($request);

            AuditService::log(
                action: 'admin.action',
                userId: $ids['user_id'],
                adminId: $ids['admin_id'],
                module: 'admin',
                platform: 'web',
                metadata: [
                    'method'      => $request->method(),
                    'url'         => $request->fullUrl(),
                    'status_code' => $response->getStatusCode(),
                ],
            );
        }

        return $response;
    }
}
