<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Log une action dans la piste d'audit.
     *
     * Actions possibles :
     *   user.registered, user.logged_in, user.suspended, user.unsuspended,
     *   user.kyc_updated, user.trust_score_updated,
     *   campaign.created, campaign.approved, campaign.rejected, campaign.status_changed,
     *   withdrawal.requested, withdrawal.processed, withdrawal.failed, withdrawal.status_changed,
     *   wallet.credited, wallet.debited,
     *   fraud.detected, fraud.resolved,
     *   profile.completed, otp.verified,
     *   admin.login, admin.config_changed, role.assigned
     */
    public static function log(
        string $action,
        ?int $userId = null,
        ?int $adminId = null,
        ?string $module = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $platform = 'api',
        array $metadata = [],
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id'        => $userId,
            'admin_id'       => $adminId,
            'action'         => $action,
            'module'         => $module,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'ip_address'     => $request?->ip(),
            'user_agent'     => $request?->userAgent(),
            'platform'       => $platform,
            'metadata'       => $metadata ?: null,
        ]);
    }

    /**
     * Extrait user_id et admin_id depuis la requête HTTP courante.
     *
     * @return array{user_id: int|null, admin_id: int|null}
     */
    public static function fromRequest(Request $request): array
    {
        $userId  = null;
        $adminId = null;

        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();
        }

        if (auth('web')->check()) {
            $adminId = auth('web')->id();
        }

        return [
            'user_id'  => $userId,
            'admin_id' => $adminId,
        ];
    }
}
