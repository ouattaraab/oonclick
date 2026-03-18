<?php

namespace App\Modules\Payment\Services;

use App\Models\EscrowEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EscrowService
{
    /**
     * Bloque le montant de la campagne en escrow lors du paiement initial.
     *
     * Crée une entrée escrow avec le statut 'locked'. Lève une exception si
     * un escrow actif (non remboursé) existe déjà pour la campagne, afin
     * d'éviter tout double dépôt.
     *
     * @param int    $campaignId  Identifiant de la campagne
     * @param int    $amount      Montant total en FCFA
     * @param string $paystackRef Référence de transaction Paystack
     * @return void
     *
     * @throws \Exception Si un escrow actif existe déjà pour cette campagne
     */
    public function lock(int $campaignId, int $amount, string $paystackRef): void
    {
        DB::transaction(function () use ($campaignId, $amount, $paystackRef) {
            $existing = EscrowEntry::where('campaign_id', $campaignId)->first();

            if ($existing && $existing->status !== 'refunded') {
                throw new \Exception(
                    "Escrow déjà existant pour la campagne #{$campaignId} (statut : {$existing->status})."
                );
            }

            EscrowEntry::create([
                'campaign_id'            => $campaignId,
                'amount_locked'          => $amount,
                'amount_released'        => 0,
                'platform_fees_collected' => 0,
                'amount_refunded'        => 0,
                'paystack_reference'     => $paystackRef,
                'status'                 => 'locked',
            ]);

            Log::info('Escrow locked', [
                'campaign_id' => $campaignId,
                'amount'      => $amount,
                'reference'   => $paystackRef,
            ]);
        });
    }

    /**
     * Libère une fraction des fonds escrow pour une vue publicitaire validée.
     *
     * - $subscriberAmount : part reversée à l'abonné
     * - $platformFee      : frais prélevés par la plateforme
     *
     * Le statut passe à 'partial' tant que le budget n'est pas entièrement
     * consommé, puis à 'released' quand le total libéré égale amount_locked.
     *
     * @param int $campaignId       Identifiant de la campagne
     * @param int $subscriberAmount Montant en FCFA à créditer à l'abonné
     * @param int $platformFee      Frais plateforme en FCFA
     * @return void
     *
     * @throws \Exception Si l'escrow est introuvable, dans un mauvais statut
     *                    ou si la libération dépasserait le montant bloqué
     */
    public function release(int $campaignId, int $subscriberAmount, int $platformFee): void
    {
        DB::transaction(function () use ($campaignId, $subscriberAmount, $platformFee) {
            /** @var EscrowEntry $escrow */
            $escrow = EscrowEntry::where('campaign_id', $campaignId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($escrow->status, ['locked', 'partial'], true)) {
                throw new \Exception(
                    "Impossible de libérer l'escrow de la campagne #{$campaignId} : statut '{$escrow->status}' invalide."
                );
            }

            $totalAfterRelease = $escrow->amount_released
                + $subscriberAmount
                + $escrow->platform_fees_collected
                + $platformFee;

            if ($totalAfterRelease > $escrow->amount_locked) {
                throw new \Exception(
                    "Dépassement escrow pour la campagne #{$campaignId} : "
                    . "libération de {$totalAfterRelease} FCFA dépasse le montant bloqué de {$escrow->amount_locked} FCFA."
                );
            }

            $escrow->amount_released          += $subscriberAmount;
            $escrow->platform_fees_collected  += $platformFee;

            $totalConsumed = $escrow->amount_released + $escrow->platform_fees_collected;
            $escrow->status = ($totalConsumed >= $escrow->amount_locked) ? 'released' : 'partial';

            $escrow->save();
        });
    }

    /**
     * Rembourse intégralement le solde escrow restant à l'annonceur.
     *
     * Calcule le montant remboursable comme :
     *   amount_locked − amount_released − platform_fees_collected
     *
     * Utilisé en cas de rejet de campagne ou d'annulation avant diffusion.
     *
     * @param int $campaignId Identifiant de la campagne à rembourser
     * @return array          ['amount' => int, 'campaign_id' => int]
     *
     * @throws \Exception Si l'escrow est introuvable
     */
    public function refund(int $campaignId): array
    {
        return DB::transaction(function () use ($campaignId) {
            /** @var EscrowEntry $escrow */
            $escrow = EscrowEntry::where('campaign_id', $campaignId)
                ->lockForUpdate()
                ->firstOrFail();

            $refundable = $escrow->amount_locked
                - $escrow->amount_released
                - $escrow->platform_fees_collected;

            $escrow->amount_refunded = $refundable;
            $escrow->status          = 'refunded';
            $escrow->save();

            Log::info('Escrow refunded', [
                'campaign_id'    => $campaignId,
                'amount_refunded' => $refundable,
            ]);

            return [
                'amount'      => $refundable,
                'campaign_id' => $campaignId,
            ];
        });
    }

    /**
     * Retourne le solde restant disponible dans l'escrow d'une campagne.
     *
     * Calcul : amount_locked − amount_released − platform_fees_collected − amount_refunded
     *
     * @param int $campaignId Identifiant de la campagne
     * @return int            Solde restant en FCFA
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getRemainingBalance(int $campaignId): int
    {
        $escrow = EscrowEntry::where('campaign_id', $campaignId)->firstOrFail();

        return $escrow->amount_locked
            - $escrow->amount_released
            - $escrow->platform_fees_collected
            - $escrow->amount_refunded;
    }
}
