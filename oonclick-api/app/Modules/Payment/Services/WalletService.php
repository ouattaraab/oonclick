<?php

namespace App\Modules\Payment\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Crédite le wallet d'un utilisateur et enregistre la transaction.
     *
     * Utilise lockForUpdate() pour éviter les race conditions en cas de
     * crédits concurrents. Le total_earned est incrémenté pour les types
     * 'credit' et 'bonus'.
     *
     * @param int         $userId      Identifiant de l'utilisateur
     * @param int         $amount      Montant à créditer en FCFA (doit être > 0)
     * @param string      $type        Type : 'credit' | 'bonus' | 'refund' | 'pending'
     * @param string      $description Libellé lisible de la transaction
     * @param array       $metadata    Données complémentaires (campaign_id, etc.)
     * @param string|null $reference   Référence unique (générée automatiquement si null)
     * @return WalletTransaction       Transaction créée
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function credit(
        int $userId,
        int $amount,
        string $type,
        string $description,
        array $metadata = [],
        ?string $reference = null
    ): WalletTransaction {
        return DB::transaction(function () use ($userId, $amount, $type, $description, $metadata, $reference) {
            /** @var Wallet $wallet */
            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance      = $wallet->balance + $amount;
            $wallet->balance = $newBalance;

            if (in_array($type, ['credit', 'bonus'], true)) {
                $wallet->total_earned += $amount;
            }

            $wallet->save();

            $txnReference = $reference ?? 'TXN_' . Str::upper(Str::random(12));

            $transaction = WalletTransaction::create([
                'wallet_id'     => $wallet->id,
                'type'          => $type,
                'amount'        => $amount,
                'balance_after' => $newBalance,
                'reference'     => $txnReference,
                'description'   => $description,
                'metadata'      => $metadata ?: null,
                'status'        => 'completed',
            ]);

            return $transaction;
        });
    }

    /**
     * Débite le wallet d'un utilisateur et enregistre la transaction.
     *
     * Vérifie que le solde est suffisant avant tout mouvement.
     * Utilise lockForUpdate() pour éviter les race conditions.
     * Le total_withdrawn est incrémenté pour le type 'debit'.
     *
     * @param int         $userId      Identifiant de l'utilisateur
     * @param int         $amount      Montant à débiter en FCFA (doit être > 0)
     * @param string      $type        Type : 'debit' | 'pending' | 'refund'
     * @param string      $description Libellé lisible de la transaction
     * @param array       $metadata    Données complémentaires (withdrawal_id, etc.)
     * @param string|null $reference   Référence unique (générée automatiquement si null)
     * @return WalletTransaction       Transaction créée
     *
     * @throws \Exception Si le solde est insuffisant
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function debit(
        int $userId,
        int $amount,
        string $type,
        string $description,
        array $metadata = [],
        ?string $reference = null
    ): WalletTransaction {
        return DB::transaction(function () use ($userId, $amount, $type, $description, $metadata, $reference) {
            /** @var Wallet $wallet */
            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $amount) {
                throw new \Exception(
                    "Solde insuffisant : solde disponible {$wallet->balance} FCFA, montant demandé {$amount} FCFA."
                );
            }

            $newBalance      = $wallet->balance - $amount;
            $wallet->balance = $newBalance;

            if ($type === 'debit') {
                $wallet->total_withdrawn += $amount;
            }

            $wallet->save();

            $txnReference = $reference ?? 'TXN_' . Str::upper(Str::random(12));

            $transaction = WalletTransaction::create([
                'wallet_id'     => $wallet->id,
                'type'          => $type,
                'amount'        => $amount,
                'balance_after' => $newBalance,
                'reference'     => $txnReference,
                'description'   => $description,
                'metadata'      => $metadata ?: null,
                'status'        => 'completed',
            ]);

            return $transaction;
        });
    }

    /**
     * Retourne le solde courant du wallet en FCFA.
     *
     * @param int $userId Identifiant de l'utilisateur
     * @return int        Solde en FCFA (0 si le wallet n'existe pas)
     */
    public function getBalance(int $userId): int
    {
        return Wallet::where('user_id', $userId)->value('balance') ?? 0;
    }

    /**
     * Crée ou récupère le wallet d'un utilisateur.
     *
     * Utilise firstOrCreate pour garantir l'idempotence.
     *
     * @param int $userId Identifiant de l'utilisateur
     * @return Wallet     Wallet existant ou nouvellement créé
     */
    public function createWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            [
                'balance'          => 0,
                'pending_balance'  => 0,
                'total_earned'     => 0,
                'total_withdrawn'  => 0,
            ]
        );
    }
}
