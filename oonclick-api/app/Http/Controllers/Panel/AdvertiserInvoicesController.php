<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Contrôleur panel annonceur — Factures (US-047).
 *
 * Affiche la liste des factures et permet le téléchargement PDF.
 */
class AdvertiserInvoicesController extends Controller
{
    /**
     * GET /panel/advertiser/invoices
     *
     * Affiche la liste paginée des factures de l'annonceur connecté.
     */
    public function index()
    {
        $user     = auth()->user();
        $invoices = Invoice::where('user_id', $user->id)
            ->with('campaign:id,title,format')
            ->latest()
            ->paginate(20);

        return view('panel.advertiser.invoices', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * GET /panel/advertiser/invoices/{invoice}/pdf
     *
     * Génère et télécharge le PDF d'une facture.
     * L'annonceur ne peut accéder qu'à ses propres factures.
     */
    public function downloadPdf(Invoice $invoice)
    {
        abort_if($invoice->user_id !== auth()->id(), 403);

        $invoice->load(['campaign', 'user']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'     => $invoice,
            'user'        => $invoice->user,
            'campaign'    => $invoice->campaign,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("facture-{$invoice->invoice_number}.pdf");
    }
}
