<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditService;

class UserObserver
{
    public function created(User $user): void
    {
        AuditService::log(
            action: 'user.registered',
            userId: $user->id,
            module: 'auth',
            auditableType: User::class,
            auditableId: $user->id,
            newValues: [
                'role'  => $user->role,
                'phone' => $this->maskPhone($user->phone),
            ],
        );
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('is_suspended')) {
            $action = $user->is_suspended ? 'user.suspended' : 'user.unsuspended';

            AuditService::log(
                action: $action,
                userId: $user->id,
                module: 'auth',
                auditableType: User::class,
                auditableId: $user->id,
                oldValues: ['is_suspended' => $user->getOriginal('is_suspended')],
                newValues: ['is_suspended' => $user->is_suspended],
            );
        }

        if ($user->wasChanged('kyc_level')) {
            AuditService::log(
                action: 'user.kyc_updated',
                userId: $user->id,
                module: 'auth',
                auditableType: User::class,
                auditableId: $user->id,
                oldValues: ['kyc_level' => $user->getOriginal('kyc_level')],
                newValues: ['kyc_level' => $user->kyc_level],
            );
        }

        if ($user->wasChanged('trust_score')) {
            AuditService::log(
                action: 'user.trust_score_updated',
                userId: $user->id,
                module: 'auth',
                auditableType: User::class,
                auditableId: $user->id,
                oldValues: ['trust_score' => $user->getOriginal('trust_score')],
                newValues: ['trust_score' => $user->trust_score],
            );
        }
    }

    private function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        // Masque le milieu : +225 07****67
        if (strlen($phone) <= 6) {
            return $phone;
        }

        $prefix = substr($phone, 0, strlen($phone) - 6);
        $suffix = substr($phone, -2);
        $masked = $prefix . '****' . $suffix;

        return $masked;
    }
}
