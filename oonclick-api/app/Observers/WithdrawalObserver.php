<?php

namespace App\Observers;

use App\Models\Withdrawal;
use App\Services\AuditService;

class WithdrawalObserver
{
    public function created(Withdrawal $withdrawal): void
    {
        AuditService::log(
            action: 'withdrawal.requested',
            userId: $withdrawal->user_id,
            module: 'payment',
            auditableType: Withdrawal::class,
            auditableId: $withdrawal->id,
            newValues: [
                'amount'           => $withdrawal->amount,
                'net_amount'       => $withdrawal->net_amount,
                'mobile_operator'  => $withdrawal->mobile_operator,
                'status'           => $withdrawal->status,
            ],
        );
    }

    public function updated(Withdrawal $withdrawal): void
    {
        if ($withdrawal->wasChanged('status')) {
            AuditService::log(
                action: 'withdrawal.status_changed',
                userId: $withdrawal->user_id,
                module: 'payment',
                auditableType: Withdrawal::class,
                auditableId: $withdrawal->id,
                oldValues: ['status' => $withdrawal->getOriginal('status')],
                newValues: ['status' => $withdrawal->status],
            );
        }
    }
}
