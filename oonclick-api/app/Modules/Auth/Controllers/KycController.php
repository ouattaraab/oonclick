<?php

namespace App\Modules\Auth\Controllers;

use App\Models\KycDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    /**
     * Soumet un document KYC pour l'utilisateur authentifié.
     *
     * POST /api/kyc/submit
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level'         => ['required', 'integer', 'in:1,2,3'],
            'document_type' => ['required', 'in:national_id,passport,business_reg,selfie,proof_of_address'],
            'file'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'level.required'         => 'Le niveau KYC est requis.',
            'level.in'               => 'Le niveau KYC doit être 1, 2 ou 3.',
            'document_type.required' => 'Le type de document est requis.',
            'document_type.in'       => 'Type de document non reconnu.',
            'file.required'          => 'Le fichier est requis.',
            'file.mimes'             => 'Le fichier doit être au format JPG, PNG ou PDF.',
            'file.max'               => 'Le fichier ne doit pas dépasser 5 Mo.',
        ]);

        $user = $request->user();

        // Stocker le fichier sur le disque R2 sous kyc/{user_id}/{document_type}_{timestamp}.{ext}
        $file      = $request->file('file');
        $ext       = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $path      = "kyc/{$user->id}/{$validated['document_type']}_{$timestamp}.{$ext}";

        Storage::disk('r2')->putFileAs(
            "kyc/{$user->id}",
            $file,
            "{$validated['document_type']}_{$timestamp}.{$ext}"
        );

        // Créer l'enregistrement KYC
        $document = KycDocument::create([
            'user_id'       => $user->id,
            'level'         => $validated['level'],
            'document_type' => $validated['document_type'],
            'file_path'     => $path,
            'file_disk'     => 'r2',
            'status'        => 'pending',
            'submitted_at'  => now(),
        ]);

        return response()->json([
            'message'  => 'Document soumis avec succès. Il sera examiné par notre équipe sous 48h.',
            'document' => [
                'id'            => $document->id,
                'level'         => $document->level,
                'document_type' => $document->document_type,
                'status'        => $document->status,
                'submitted_at'  => $document->submitted_at,
            ],
        ], 201);
    }

    /**
     * Retourne le statut KYC global de l'utilisateur authentifié,
     * avec les documents groupés par niveau.
     *
     * GET /api/kyc/status
     */
    public function status(Request $request): JsonResponse
    {
        $user      = $request->user();
        $documents = KycDocument::where('user_id', $user->id)
            ->orderByDesc('submitted_at')
            ->get(['id', 'level', 'document_type', 'status', 'rejection_reason', 'submitted_at', 'reviewed_at']);

        // Grouper par niveau
        $byLevel = $documents->groupBy('level')->map(function ($docs, $level) {
            $allApproved = $docs->every(fn ($d) => $d->status === 'approved');
            $anyPending  = $docs->contains(fn ($d) => $d->status === 'pending');
            $anyRejected = $docs->contains(fn ($d) => $d->status === 'rejected');

            $levelStatus = match (true) {
                $allApproved && $docs->count() > 0 => 'approved',
                $anyRejected                       => 'rejected',
                $anyPending                        => 'pending',
                default                            => 'not_submitted',
            };

            return [
                'level'     => (int) $level,
                'status'    => $levelStatus,
                'documents' => $docs->values(),
            ];
        });

        return response()->json([
            'kyc_level'      => $user->kyc_level,
            'levels'         => $byLevel->values(),
            'overall_status' => $this->computeOverallStatus($user->kyc_level, $byLevel),
        ]);
    }

    /**
     * Retourne la liste paginée des documents KYC de l'utilisateur.
     *
     * GET /api/kyc/documents
     */
    public function documents(Request $request): JsonResponse
    {
        $user      = $request->user();
        $documents = KycDocument::where('user_id', $user->id)
            ->orderByDesc('submitted_at')
            ->paginate(20, [
                'id', 'level', 'document_type', 'status',
                'rejection_reason', 'submitted_at', 'reviewed_at',
            ]);

        return response()->json($documents);
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    /**
     * Calcule le statut global en fonction du niveau KYC actuel et des documents.
     */
    private function computeOverallStatus(int $kycLevel, $byLevel): string
    {
        if ($kycLevel >= 3) {
            return 'fully_verified';
        }

        // Vérifier si des documents sont en attente sur un niveau supérieur
        $nextLevel = $kycLevel + 1;
        $nextLevelData = $byLevel->firstWhere('level', $nextLevel);

        if ($nextLevelData && $nextLevelData['status'] === 'pending') {
            return 'under_review';
        }

        if ($kycLevel === 0) {
            return 'not_started';
        }

        return "level_{$kycLevel}_verified";
    }
}
