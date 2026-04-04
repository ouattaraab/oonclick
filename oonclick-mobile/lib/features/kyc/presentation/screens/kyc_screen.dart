import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/models/kyc_model.dart';
import '../providers/kyc_provider.dart';

/// Écran de vérification d'identité (KYC).
class KycScreen extends ConsumerWidget {
  const KycScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final statusAsync = ref.watch(kycProvider);
    final docsAsync = ref.watch(kycDocumentsProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _KycTopBar(),
          Expanded(
            child: statusAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _ErrorView(
                message: err.toString(),
                onRetry: () {
                  ref.read(kycProvider.notifier).refresh();
                  ref.read(kycDocumentsProvider.notifier).refresh();
                },
              ),
              data: (status) => RefreshIndicator(
                color: AppColors.sky,
                onRefresh: () async {
                  ref.read(kycProvider.notifier).refresh();
                  ref.read(kycDocumentsProvider.notifier).refresh();
                },
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 20, 16, 40),
                  children: [
                    // KYC level progress
                    _KycProgressCard(status: status),
                    const SizedBox(height: 20),

                    // Level 1
                    _KycLevelSection(
                      level: 1,
                      title: 'Niveau 1 — Identité',
                      description:
                          'Pièce d\'identité nationale + selfie. Débloque 10 000 F/mois de retrait.',
                      isUnlocked: true,
                      isCompleted: status.kycLevel >= 1,
                      documents: [
                        _DocRequirement(
                          type: 'national_id',
                          label: 'Pièce d\'identité nationale',
                          icon: Icons.badge_rounded,
                        ),
                        _DocRequirement(
                          type: 'selfie',
                          label: 'Selfie avec pièce d\'identité',
                          icon: Icons.face_rounded,
                        ),
                      ],
                      docsAsync: docsAsync,
                    ),
                    const SizedBox(height: 16),

                    // Level 2
                    _KycLevelSection(
                      level: 2,
                      title: 'Niveau 2 — Professionnel',
                      description:
                          'Registre de commerce ou attestation fiscale. Débloque 50 000 F/mois.',
                      isUnlocked: status.kycLevel >= 1,
                      isCompleted: status.kycLevel >= 2,
                      documents: [
                        _DocRequirement(
                          type: 'business_registration',
                          label: 'Registre de commerce',
                          icon: Icons.business_rounded,
                        ),
                      ],
                      docsAsync: docsAsync,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _KycTopBar extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        MediaQuery.of(context).padding.top + 12,
        16,
        14,
      ),
      decoration: const BoxDecoration(gradient: AppColors.navyGradient),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => context.pop(),
            child: Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(30),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.arrow_back_ios_new_rounded,
                  color: Colors.white, size: 15),
            ),
          ),
          const SizedBox(width: 12),
          Text(
            'Vérification d\'identité',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          const Text('🪪', style: TextStyle(fontSize: 22)),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// KYC progress card
// ---------------------------------------------------------------------------

class _KycProgressCard extends StatelessWidget {
  const _KycProgressCard({required this.status});

  final KycStatusModel status;

  String _statusLabel(String s) => switch (s) {
        'approved' => 'Approuvé',
        'pending' => 'En attente',
        'rejected' => 'Refusé',
        _ => 'Non commencé',
      };

  Color _statusColor(String s) => switch (s) {
        'approved' => AppColors.success,
        'pending' => AppColors.warn,
        'rejected' => AppColors.danger,
        _ => AppColors.muted,
      };

  @override
  Widget build(BuildContext context) {
    const maxLevel = 2;
    final progress = (status.kycLevel / maxLevel).clamp(0.0, 1.0);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: AppColors.skyGradientDiagonal,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(60),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.white.withAlpha(40),
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    '${status.kycLevel}',
                    style: GoogleFonts.nunito(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Niveau KYC ${status.kycLevel} / $maxLevel',
                      style: GoogleFonts.nunito(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      _statusLabel(status.overallStatus),
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        color: Colors.white.withAlpha(210),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 10,
              backgroundColor: Colors.white.withAlpha(50),
              valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            status.kycLevel >= maxLevel
                ? 'Vérification complète — Retrait illimité'
                : 'Niveau ${status.kycLevel + 1} disponible — soumettez vos documents',
            style: GoogleFonts.nunito(
              fontSize: 11,
              color: Colors.white.withAlpha(210),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// KYC level section
// ---------------------------------------------------------------------------

class _DocRequirement {
  const _DocRequirement({
    required this.type,
    required this.label,
    required this.icon,
  });

  final String type;
  final String label;
  final IconData icon;
}

class _KycLevelSection extends ConsumerStatefulWidget {
  const _KycLevelSection({
    required this.level,
    required this.title,
    required this.description,
    required this.isUnlocked,
    required this.isCompleted,
    required this.documents,
    required this.docsAsync,
  });

  final int level;
  final String title;
  final String description;
  final bool isUnlocked;
  final bool isCompleted;
  final List<_DocRequirement> documents;
  final AsyncValue<List<KycDocumentModel>> docsAsync;

  @override
  ConsumerState<_KycLevelSection> createState() =>
      _KycLevelSectionState();
}

class _KycLevelSectionState
    extends ConsumerState<_KycLevelSection> {
  bool _uploading = false;
  String? _uploadingType;

  Future<void> _pickAndUpload(String type) async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );

    if (pickedFile == null) return;

    setState(() {
      _uploading = true;
      _uploadingType = type;
    });

    try {
      await ref.read(kycDocumentsProvider.notifier).submit(
            level: widget.level,
            type: type,
            file: File(pickedFile.path),
          );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Document soumis avec succès !',
              style: GoogleFonts.nunito(),
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.toString().replaceAll('Exception: ', ''),
              style: GoogleFonts.nunito(),
            ),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _uploading = false;
          _uploadingType = null;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final submittedDocs = widget.docsAsync.valueOrNull ?? [];
    final levelDocs =
        submittedDocs.where((d) => d.level == widget.level).toList();

    return Container(
      decoration: BoxDecoration(
        color: widget.isUnlocked ? AppColors.white : AppColors.bg,
        border: Border.all(
          color: widget.isCompleted
              ? AppColors.success.withAlpha(80)
              : AppColors.border,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: widget.isCompleted
                        ? AppColors.successLight
                        : widget.isUnlocked
                            ? AppColors.skyPale
                            : AppColors.bg,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    widget.isCompleted
                        ? Icons.check_circle_rounded
                        : widget.isUnlocked
                            ? Icons.lock_open_rounded
                            : Icons.lock_rounded,
                    color: widget.isCompleted
                        ? AppColors.success
                        : widget.isUnlocked
                            ? AppColors.sky
                            : AppColors.muted,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        widget.title,
                        style: GoogleFonts.nunito(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: widget.isUnlocked
                              ? AppColors.navy
                              : AppColors.muted,
                        ),
                      ),
                      Text(
                        widget.description,
                        style: GoogleFonts.nunito(
                          fontSize: 11,
                          color: AppColors.muted,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Divider
          const SizedBox(height: 12),
          Divider(height: 1, color: AppColors.border),
          const SizedBox(height: 12),

          // Documents
          ...widget.documents.map((doc) {
            final submitted = levelDocs
                .where((d) => d.documentType == doc.type)
                .toList();
            final latest =
                submitted.isNotEmpty ? submitted.last : null;

            return _DocumentRow(
              doc: doc,
              submitted: latest,
              isUnlocked: widget.isUnlocked && !widget.isCompleted,
              isUploading: _uploading && _uploadingType == doc.type,
              onUpload: () => _pickAndUpload(doc.type),
            );
          }),

          const SizedBox(height: 8),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Document row
// ---------------------------------------------------------------------------

class _DocumentRow extends StatelessWidget {
  const _DocumentRow({
    required this.doc,
    required this.submitted,
    required this.isUnlocked,
    required this.isUploading,
    required this.onUpload,
  });

  final _DocRequirement doc;
  final KycDocumentModel? submitted;
  final bool isUnlocked;
  final bool isUploading;
  final VoidCallback onUpload;

  Color _statusColor(String? status) => switch (status) {
        'approved' => AppColors.success,
        'rejected' => AppColors.danger,
        'pending' => AppColors.warn,
        _ => AppColors.muted,
      };

  Color _statusBg(String? status) => switch (status) {
        'approved' => AppColors.successLight,
        'rejected' => AppColors.dangerLight,
        'pending' => AppColors.warnLight,
        _ => AppColors.bg,
      };

  @override
  Widget build(BuildContext context) {
    final hasDocument = submitted != null;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: AppColors.skyPale,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(doc.icon, color: AppColors.sky, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  doc.label,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy,
                  ),
                ),
                if (hasDocument) ...[
                  const SizedBox(height: 2),
                  if (submitted!.isRejected &&
                      submitted!.rejectionReason != null) ...[
                    Text(
                      submitted!.rejectionReason!,
                      style: GoogleFonts.nunito(
                        fontSize: 10,
                        color: AppColors.danger,
                      ),
                    ),
                  ],
                ],
              ],
            ),
          ),
          const SizedBox(width: 8),

          // Status badge or upload button
          if (hasDocument)
            Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: _statusBg(submitted!.status),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                submitted!.statusLabel,
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: _statusColor(submitted!.status),
                ),
              ),
            )
          else if (isUnlocked)
            GestureDetector(
              onTap: isUploading ? null : onUpload,
              child: Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  gradient: AppColors.skyGradient,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: isUploading
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Text(
                        'Envoyer',
                        style: GoogleFonts.nunito(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
              ),
            )
          else
            Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.bg,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AppColors.border),
              ),
              child: Text(
                'Verrouillé',
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  color: AppColors.muted,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Error view
// ---------------------------------------------------------------------------

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline_rounded,
                size: 56, color: AppColors.danger),
            const SizedBox(height: 16),
            Text(message,
                textAlign: TextAlign.center,
                style: GoogleFonts.nunito(color: AppColors.muted)),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: onRetry,
              child: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }
}
