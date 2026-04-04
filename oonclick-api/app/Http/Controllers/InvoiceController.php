<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Contrôleur de facturation API (US-047).
 *
 * Permet à l'annonceur de lister et télécharger ses factures.
 */
class InvoiceController extends Controller
{
    /**
     * GET /api/invoices
     *
     * Retourne la liste paginée des factures de l'utilisateur authentifié.
     */
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::where('user_id', $request->user()->id)
            ->with('campaign:id,title,format')
            ->latest()
            ->paginate(20);

        return response()->json([
            'invoices' => $invoices->map(fn ($invoice) => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'type'           => $invoice->type,
                'amount'         => $invoice->amount,
                'tax_amount'     => $invoice->tax_amount,
                'total_amount'   => $invoice->total_amount,
                'status'         => $invoice->status,
                'paid_at'        => $invoice->paid_at?->toDateTimeString(),
                'due_date'       => $invoice->due_date?->toDateString(),
                'campaign'       => $invoice->campaign ? [
                    'id'     => $invoice->campaign->id,
                    'title'  => $invoice->campaign->title,
                    'format' => $invoice->campaign->format,
                ] : null,
                'created_at'     => $invoice->created_at->toDateTimeString(),
            ]),
            'total'        => $invoices->total(),
            'current_page' => $invoices->currentPage(),
            'last_page'    => $invoices->lastPage(),
        ]);
    }

    /**
     * GET /api/invoices/{id}/pdf
     *
     * Génère et télécharge le PDF d'une facture.
     * L'utilisateur authentifié ne peut accéder qu'à ses propres factures.
     */
    public function downloadPdf(Request $request, int $id): Response
    {
        $invoice = Invoice::where('user_id', $request->user()->id)
            ->with(['campaign', 'user'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'     => $invoice,
            'user'        => $invoice->user,
            'campaign'    => $invoice->campaign,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = "facture-{$invoice->invoice_number}.pdf";

        return $pdf->download($filename);
    }
}
