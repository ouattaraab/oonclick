<?php

namespace App\Http\Controllers;

use App\Models\AppInstall;
use App\Models\AppVersion;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /**
     * GET /app/version?platform=android&version=1.0.0
     * Public — aucune authentification requise.
     */
    public function version(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => ['required', 'in:android,ios'],
            'version'  => ['nullable', 'string', 'max:20'],
        ]);

        $appVersion = AppVersion::forPlatform($request->input('platform'));

        if (! $appVersion) {
            return response()->json(['message' => 'Version non trouvée pour cette plateforme.'], 404);
        }

        $currentVersion = $request->input('version');
        $forceUpdate    = false;

        if ($currentVersion) {
            $forceUpdate = $appVersion->isForced($currentVersion);
        }

        return response()->json([
            'latest_version' => $appVersion->latest_version,
            'min_version'    => $appVersion->min_version,
            'force_update'   => $forceUpdate,
            'store_url'      => $appVersion->store_url,
            'release_notes'  => $appVersion->release_notes,
        ]);
    }

    /**
     * POST /app/register-install
     * Public — aucune authentification requise.
     */
    public function registerInstall(Request $request): JsonResponse
    {
        $data = $request->validate([
            'install_id'   => ['required', 'string', 'max:64'],
            'platform'     => ['required', 'in:android,ios,web'],
            'app_version'  => ['nullable', 'string', 'max:20'],
            'os_version'   => ['nullable', 'string', 'max:50'],
            'device_model' => ['nullable', 'string', 'max:100'],
        ]);

        $installId = $data['install_id'];
        unset($data['install_id']);

        $install = AppInstall::registerOrUpdate($installId, $data);

        return response()->json([
            'success'      => true,
            'install_id'   => $install->install_id,
            'launch_count' => $install->launch_count,
        ]);
    }

    /**
     * POST /app/audit-event
     * Auth: sanctum requis.
     */
    public function auditEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action'   => ['required', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $userId = auth('sanctum')->id();

        AuditService::log(
            action: $data['action'],
            userId: $userId,
            module: 'mobile',
            platform: 'mobile',
            metadata: $data['metadata'] ?? [],
        );

        return response()->json(['success' => true]);
    }
}
