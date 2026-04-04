<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Service de génération automatique des factures (US-047).
 *
 * Génère des factures lors du paiement d'une campagne.
 * Le numéro de facture suit le format OON-YYYY-XXXX (séquentiel par année).
 */
class InvoiceService
{
    /**
     * Génère une facture pour le paiement d'une campagne.
     *
     * La facture est créée avec le statut "paid" car le paiement est
     * effectué au moment de la création de la campagne (pré-paiement).
     *
     * @param Campaign $campaign Campagne pour laquelle générer la facture
     * @return Invoice           Facture créée
     */
    public function generateForCampaign(Campaign $campaign): Invoice
    {
        return DB::transaction(function () use ($campaign) {
            $amount      = $campaign->budget;
            $taxAmount   = 0; // TVA non applicable pour le moment
            $totalAmount = $amount + $taxAmount;

            $invoice = Invoice::create([
                'user_id'        => $campaign->advertiser_id,
                'campaign_id'    => $campaign->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'type'           => 'campaign_payment',
                'amount'         => $amount,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $totalAmount,
                'status'         => 'paid',
                'paid_at'        => now(),
                'due_date'       => now()->toDateString(),
                'metadata'       => [
                    'campaign_title'  => $campaign->title,
                    'campaign_format' => $campaign->format,
                    'cost_per_view'   => $campaign->cost_per_view,
                    'max_views'       => $campaign->max_views,
                ],
            ]);

            return $invoice;
        });
    }

    /**
     * Génère un numéro de facture unique au format OON-YYYY-XXXX.
     *
     * Le compteur est séquentiel par année civile. En cas de concurrence,
     * la boucle réessaie jusqu'à trouver un numéro libre.
     *
     * @return string Numéro de facture (ex: OON-2026-0001)
     */
    public function generateInvoiceNumber(): string
    {
        $year = now()->year;

        // Compter les factures de l'année en cours
        $count = Invoice::whereYear('created_at', $year)->count();

        do {
            $count++;
            $number = sprintf('OON-%d-%04d', $year, $count);
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
