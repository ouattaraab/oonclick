<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminKycController extends Controller
{
    /**
     * Affiche la liste des documents KYC en attente de validation.
     */
    public function index()
    {
        $documents    = KycDocument::with('user')
            ->where('status', 'pending')
            ->orderBy('submitted_at')
            ->paginate(20);

        $pendingCount  = KycDocument::where('status', 'pending')->count();
        $approvedCount = KycDocument::where('status', 'approved')->count();
        $rejectedCount = KycDocument::where('status', 'rejected')->count();

        return view('panel.admin.kyc', compact(
            'documents', 'pendingCount', 'approvedCount', 'rejectedCount'
        ));
    }

    /**
     * Approuve un document KYC.
     * Si tous les documents du niveau sont approuvés, met à jour le kyc_level
     * de l'utilisateur.
     *
     * POST /panel/admin/kyc/{document}/approve
     */
    public function approve(KycDocument $document)
    {
        if (! $document->isPending()) {
            return back()->with('error', 'Ce document ne peut pas être approuvé (statut actuel : ' . $document->status . ').');
        }

        DB::transaction(function () use ($document) {
            // Marquer le document comme approuvé
            $document->update([
                'status'      => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            // Vérifier si tous les documents du niveau sont approuvés
            $levelDocs = KycDocument::where('user_id', $document->user_id)
                ->where('level', $document->level)
                ->get();

            $allApproved = $levelDocs->every(fn ($d) => $d->status === 'approved');

            if ($allApproved) {
                // Élever le kyc_level de l'utilisateur si ce niveau est supérieur au niveau actuel
                $user = $document->user;

                if ($document->level > $user->kyc_level) {
                    $user->update(['kyc_level' => $document->level]);
                }
            }
        });

        return back()->with('success', "Document approuvé. Niveau KYC de l'utilisateur mis à jour si applicable.");
    }

    /**
     * Rejette un document KYC avec un motif obligatoire.
     *
     * POST /panel/admin/kyc/{document}/reject
     */
    public function reject(KycDocument $document, Request $request)
    {
        if (! $document->isPending()) {
            return back()->with('error', 'Ce document ne peut pas être rejeté (statut actuel : ' . $document->status . ').');
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Le motif de rejet est obligatoire.',
            'rejection_reason.max'      => 'Le motif ne peut pas dépasser 500 caractères.',
        ]);

        $document->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        return back()->with('success', 'Document rejeté avec succès.');
    }
}
