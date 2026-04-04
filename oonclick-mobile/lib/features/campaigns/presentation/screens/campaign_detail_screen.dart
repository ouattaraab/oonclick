import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/campaign_model.dart';
import '../../data/repositories/campaign_repository.dart';
import '../providers/campaign_provider.dart';
import '../widgets/campaign_format_badge.dart';
import '../widgets/campaign_stat_card.dart';
import '../widgets/campaign_status_badge.dart';

// ---------------------------------------------------------------------------
// CampaignDetailScreen
// ---------------------------------------------------------------------------

class CampaignDetailScreen extends ConsumerStatefulWidget {
  const CampaignDetailScreen({super.key, required this.campaignId});

  final int campaignId;

  @override
  ConsumerState<CampaignDetailScreen> createState() =>
      _CampaignDetailScreenState();
}

class _CampaignDetailScreenState extends ConsumerState<CampaignDetailScreen> {
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    // Lance le polling uniquement après le premier build, quand les données
    // sont disponibles.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _startPollingIfActive();
    });
  }

  void _startPollingIfActive() {
    final campaign = ref
        .read(campaignDetailProvider(widget.campaignId))
        .valueOrNull
        ?.campaign;
    if (campaign?.status == CampaignStatus.active) {
      _pollTimer?.cancel();
      _pollTimer = Timer.periodic(const Duration(seconds: 10), (_) {
        ref
            .read(campaignDetailProvider(widget.campaignId).notifier)
            .refreshSilent();
      });
    }
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final detailAsync = ref.watch(campaignDetailProvider(widget.campaignId));

    // Si la campagne change de statut (ex. passe à active), on relance le
    // polling de façon réactive.
    ref.listen(campaignDetailProvider(widget.campaignId), (prev, next) {
      final prevStatus = prev?.valueOrNull?.campaign.status;
      final nextStatus = next.valueOrNull?.campaign.status;
      if (prevStatus != nextStatus) {
        if (nextStatus == CampaignStatus.active) {
          _startPollingIfActive();
        } else {
          _pollTimer?.cancel();
          _pollTimer = null;
        }
      }
    });

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: detailAsync.when(
        loading: () => const _LoadingState(),
        error: (err, _) => _ErrorState(
          message: err.toString(),
          onRetry: () => ref
              .read(campaignDetailProvider(widget.campaignId).notifier)
              .refresh(),
          onBack: () => context.pop(),
        ),
        data: (detail) => _DetailBody(
          detail: detail,
          campaignId: widget.campaignId,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Corps du détail
// ---------------------------------------------------------------------------

class _DetailBody extends ConsumerStatefulWidget {
  const _DetailBody({
    required this.detail,
    required this.campaignId,
  });

  final CampaignDetailModel detail;
  final int campaignId;

  @override
  ConsumerState<_DetailBody> createState() => _DetailBodyState();
}

class _DetailBodyState extends ConsumerState<_DetailBody> {
  bool _isActing = false;

  Future<void> _executeAction(Future<void> Function() action) async {
    setState(() => _isActing = true);
    try {
      await action();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Action effectuée avec succès',
              style: GoogleFonts.nunito()),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString(), style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) setState(() => _isActing = false);
    }
  }

  Future<void> _confirmDelete(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Supprimer la campagne',
            style: GoogleFonts.nunito(fontWeight: FontWeight.w800)),
        content: Text(
          'Cette action est irréversible. La campagne sera supprimée définitivement.',
          style: GoogleFonts.nunito(color: AppColors.muted),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text('Annuler',
                style: GoogleFonts.nunito(color: AppColors.muted)),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text('Supprimer',
                style: GoogleFonts.nunito(
                  color: AppColors.danger,
                  fontWeight: FontWeight.w700,
                )),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _isActing = true);
    try {
      await ref.read(campaignRepositoryProvider).deleteCampaign(widget.campaignId);
      ref
          .read(campaignsProvider.notifier)
          .removeCampaignLocally(widget.campaignId);
      if (!mounted) return;
      // ignore: use_build_context_synchronously
      context.pop();
    } catch (e) {
      if (!mounted) return;
      // ignore: use_build_context_synchronously
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString(), style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) setState(() => _isActing = false);
    }
  }

  Future<void> _confirmDuplicate() async {
    setState(() => _isActing = true);
    try {
      await ref
          .read(campaignRepositoryProvider)
          .duplicateCampaign(widget.campaignId);
      ref.read(campaignsProvider.notifier).refresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Campagne dupliquée en brouillon',
              style: GoogleFonts.nunito()),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString(), style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) setState(() => _isActing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final campaign = widget.detail.campaign;

    return CustomScrollView(
      slivers: [
        // App bar avec média en hero
        _CampaignSliverAppBar(campaign: campaign),

        SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              // Titre + badges
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      campaign.title,
                      style: GoogleFonts.nunito(
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
                        color: AppColors.navy,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  CampaignStatusBadge(status: campaign.status, large: true),
                ],
              ),

              const SizedBox(height: 6),

              // Badge EN DIRECT animé + format
              Row(
                children: [
                  CampaignFormatBadge(format: campaign.format),
                  if (campaign.isActive) ...[
                    const SizedBox(width: 8),
                    const _LiveBadge(),
                  ],
                ],
              ),

              // Mode de fin de campagne
              const SizedBox(height: 8),
              _EndModeChip(endModeLabel: campaign.endModeLabel),

              // Description
              if (campaign.description != null &&
                  campaign.description!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  campaign.description!,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    color: AppColors.muted,
                    height: 1.5,
                  ),
                ),
              ],

              // Motif de rejet
              if (campaign.isRejected &&
                  campaign.rejectionReason != null) ...[
                const SizedBox(height: 14),
                _RejectionBanner(reason: campaign.rejectionReason!),
              ],

              const SizedBox(height: 20),

              // Statistiques — grille 2 colonnes
              Text(
                'Statistiques',
                style: GoogleFonts.nunito(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: AppColors.navy,
                ),
              ),
              const SizedBox(height: 10),

              _AnimatedStatsGrid(
                detail: widget.detail,
                campaign: campaign,
              ),

              // Ciblage
              if (campaign.targeting != null &&
                  campaign.targeting!.isNotEmpty) ...[
                const SizedBox(height: 20),
                _TargetingSection(targeting: campaign.targeting!),
              ],

              // Dates
              const SizedBox(height: 20),
              _DatesSection(campaign: campaign),

              // Actions
              const SizedBox(height: 24),
              _ActionsSection(
                campaign: campaign,
                isActing: _isActing,
                onSubmit: () => _executeAction(
                  () => ref
                      .read(campaignDetailProvider(widget.campaignId).notifier)
                      .submit(),
                ),
                onPause: () => _executeAction(
                  () => ref
                      .read(campaignDetailProvider(widget.campaignId).notifier)
                      .pause(),
                ),
                onResume: () => _executeAction(
                  () => ref
                      .read(campaignDetailProvider(widget.campaignId).notifier)
                      .resume(),
                ),
                onEdit: () => context
                    .push('/campaigns/${widget.campaignId}/edit'),
                onDelete: () => _confirmDelete(context),
                onDuplicate: _confirmDuplicate,
              ),
            ]),
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Badge "EN DIRECT" avec animation de pulsation
// ---------------------------------------------------------------------------

class _LiveBadge extends StatefulWidget {
  const _LiveBadge();

  @override
  State<_LiveBadge> createState() => _LiveBadgeState();
}

class _LiveBadgeState extends State<_LiveBadge>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;
  late final Animation<double> _opacityAnim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    )..repeat(reverse: true);
    _opacityAnim = Tween<double>(begin: 0.3, end: 1.0).animate(
      CurvedAnimation(parent: _ctrl, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _opacityAnim,
      builder: (context, child) => Opacity(
        opacity: _opacityAnim.value,
        child: child,
      ),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: AppColors.successLight,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: AppColors.success.withAlpha(80)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 6,
              height: 6,
              decoration: const BoxDecoration(
                color: AppColors.success,
                shape: BoxShape.circle,
              ),
            ),
            const SizedBox(width: 5),
            Text(
              'EN DIRECT',
              style: GoogleFonts.nunito(
                fontSize: 10,
                fontWeight: FontWeight.w800,
                color: AppColors.success,
                letterSpacing: 0.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Puce mode de fin
// ---------------------------------------------------------------------------

class _EndModeChip extends StatelessWidget {
  const _EndModeChip({required this.endModeLabel});
  final String endModeLabel;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        const Icon(Icons.flag_outlined, size: 13, color: AppColors.muted),
        const SizedBox(width: 5),
        Text(
          endModeLabel,
          style: GoogleFonts.nunito(
            fontSize: 12,
            color: AppColors.muted,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Grille de statistiques avec animation des valeurs
// ---------------------------------------------------------------------------

class _AnimatedStatsGrid extends StatelessWidget {
  const _AnimatedStatsGrid({
    required this.detail,
    required this.campaign,
  });

  final CampaignDetailModel detail;
  final CampaignModel campaign;

  @override
  Widget build(BuildContext context) {
    final budgetProgress = campaign.budget > 0
        ? (detail.budgetUsed / campaign.budget).clamp(0.0, 1.0)
        : 0.0;

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 10,
      mainAxisSpacing: 10,
      childAspectRatio: 1.35,
      children: [
        _AnimatedStatCard(
          label: 'Budget utilisé',
          value: Formatters.currency(detail.budgetUsed),
          icon: Icons.account_balance_wallet_rounded,
          iconColor: AppColors.sky,
          subtitle: 'sur ${Formatters.currency(campaign.budget)}',
          progress: budgetProgress,
        ),
        _AnimatedStatCard(
          label: 'Vues enregistrées',
          value: Formatters.compact(detail.viewsCount),
          icon: Icons.visibility_rounded,
          iconColor: AppColors.success,
          subtitle: 'sur ${Formatters.compact(campaign.maxViews)}',
          progress: campaign.viewsProgress,
        ),
        _AnimatedStatCard(
          label: 'Vues restantes',
          value: Formatters.compact(detail.remainingViews),
          icon: Icons.remove_red_eye_outlined,
          iconColor: AppColors.warn,
        ),
        _AnimatedStatCard(
          label: 'Coût par vue',
          value: Formatters.currency(campaign.costPerView),
          icon: Icons.trending_up_rounded,
          iconColor: AppColors.sky2,
        ),
      ],
    );
  }
}

/// Carte de statistique avec barre de progression animée via TweenAnimationBuilder.
class _AnimatedStatCard extends StatelessWidget {
  const _AnimatedStatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.iconColor,
    this.subtitle,
    this.progress,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color iconColor;
  final String? subtitle;
  final double? progress;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween<double>(begin: 0, end: progress ?? 0),
      duration: const Duration(milliseconds: 600),
      curve: Curves.easeOut,
      builder: (context, animatedProgress, _) {
        return CampaignStatCard(
          label: label,
          value: value,
          icon: icon,
          iconColor: iconColor,
          subtitle: subtitle,
          progress: progress != null ? animatedProgress : null,
        );
      },
    );
  }
}

// ---------------------------------------------------------------------------
// SliverAppBar avec image hero
// ---------------------------------------------------------------------------

class _CampaignSliverAppBar extends StatelessWidget {
  const _CampaignSliverAppBar({required this.campaign});
  final CampaignModel campaign;

  @override
  Widget build(BuildContext context) {
    final imageUrl = campaign.thumbnailUrl ?? campaign.mediaUrl;

    return SliverAppBar(
      expandedHeight: 200,
      pinned: true,
      backgroundColor: AppColors.navy,
      leading: GestureDetector(
        onTap: () => context.pop(),
        child: Container(
          margin: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Colors.black.withAlpha(60),
            borderRadius: BorderRadius.circular(10),
          ),
          child: const Icon(Icons.arrow_back_ios_new_rounded,
              color: Colors.white, size: 16),
        ),
      ),
      actions: [
        GestureDetector(
          onTap: () {},
          child: Container(
            margin: const EdgeInsets.all(8),
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.black.withAlpha(60),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.more_vert_rounded,
                color: Colors.white, size: 18),
          ),
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: imageUrl != null && imageUrl.isNotEmpty
            ? Image.network(
                imageUrl,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) =>
                    _MediaPlaceholder(format: campaign.format),
              )
            : _MediaPlaceholder(format: campaign.format),
      ),
    );
  }
}

class _MediaPlaceholder extends StatelessWidget {
  const _MediaPlaceholder({required this.format});
  final CampaignFormat format;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(gradient: AppColors.navyGradient),
      child: Center(
        child: Icon(
          switch (format) {
            CampaignFormat.video => Icons.play_circle_fill_rounded,
            CampaignFormat.scratch => Icons.auto_awesome_rounded,
            CampaignFormat.quiz => Icons.quiz_rounded,
            CampaignFormat.flash => Icons.bolt_rounded,
          },
          size: 64,
          color: Colors.white.withAlpha(80),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Bannière de rejet
// ---------------------------------------------------------------------------

class _RejectionBanner extends StatelessWidget {
  const _RejectionBanner({required this.reason});
  final String reason;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.dangerLight,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.danger.withAlpha(60)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.cancel_outlined,
              color: AppColors.danger, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Campagne rejetée',
                  style: GoogleFonts.nunito(
                    fontWeight: FontWeight.w800,
                    fontSize: 13,
                    color: AppColors.danger,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  reason,
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    color: AppColors.danger,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section ciblage
// ---------------------------------------------------------------------------

class _TargetingSection extends StatelessWidget {
  const _TargetingSection({required this.targeting});
  final Map<String, dynamic> targeting;

  @override
  Widget build(BuildContext context) {
    final ageMin = targeting['age_min'];
    final ageMax = targeting['age_max'];
    final gender = targeting['gender'];
    final interests = targeting['interests'];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Ciblage',
          style: GoogleFonts.nunito(
            fontSize: 15,
            fontWeight: FontWeight.w800,
            color: AppColors.navy,
          ),
        ),
        const SizedBox(height: 10),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (ageMin != null || ageMax != null)
                _TargetRow(
                  icon: Icons.person_outline_rounded,
                  label: 'Tranche d\'âge',
                  value:
                      '${ageMin ?? '?'} – ${ageMax ?? '?'} ans',
                ),
              if (gender != null && gender.toString().isNotEmpty)
                _TargetRow(
                  icon: Icons.people_outline_rounded,
                  label: 'Genre',
                  value: _genderLabel(gender.toString()),
                ),
              if (interests is List && interests.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  'Centres d\'intérêt',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: interests
                      .map<Widget>(
                        (i) => Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.skyPale,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Text(
                            i.toString(),
                            style: GoogleFonts.nunito(
                              fontSize: 12,
                              color: AppColors.sky2,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      )
                      .toList(),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  String _genderLabel(String gender) => switch (gender) {
        'male' => 'Hommes',
        'female' => 'Femmes',
        'all' => 'Tous',
        _ => gender,
      };
}

class _TargetRow extends StatelessWidget {
  const _TargetRow({
    required this.icon,
    required this.label,
    required this.value,
  });
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.sky),
          const SizedBox(width: 8),
          Text(
            '$label : ',
            style: GoogleFonts.nunito(
              fontSize: 12,
              color: AppColors.muted,
            ),
          ),
          Text(
            value,
            style: GoogleFonts.nunito(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppColors.navy,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section dates
// ---------------------------------------------------------------------------

class _DatesSection extends StatelessWidget {
  const _DatesSection({required this.campaign});
  final CampaignModel campaign;

  @override
  Widget build(BuildContext context) {
    String fmt(String? iso) {
      if (iso == null) return '—';
      try {
        return Formatters.date(DateTime.parse(iso));
      } catch (_) {
        return iso;
      }
    }

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          _DateRow(label: 'Créée le', value: fmt(campaign.createdAt)),
          if (campaign.startsAt != null)
            _DateRow(
              label: 'Début',
              value: fmt(campaign.startsAt),
            ),
          if (campaign.endsAt != null)
            _DateRow(
              label: 'Fin prévue',
              value: fmt(campaign.endsAt),
              isLast: true,
            ),
          if (campaign.approvedAt != null)
            _DateRow(
              label: 'Approuvée le',
              value: fmt(campaign.approvedAt),
              isLast: campaign.endsAt == null,
            ),
        ],
      ),
    );
  }
}

class _DateRow extends StatelessWidget {
  const _DateRow({
    required this.label,
    required this.value,
    this.isLast = false,
  });
  final String label;
  final String value;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        border: isLast
            ? null
            : const Border(
                bottom: BorderSide(color: AppColors.border, width: 1)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style:
                GoogleFonts.nunito(fontSize: 13, color: AppColors.muted),
          ),
          Text(
            value,
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppColors.navy,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section actions
// ---------------------------------------------------------------------------

class _ActionsSection extends StatelessWidget {
  const _ActionsSection({
    required this.campaign,
    required this.isActing,
    required this.onSubmit,
    required this.onPause,
    required this.onResume,
    required this.onEdit,
    required this.onDelete,
    required this.onDuplicate,
  });

  final CampaignModel campaign;
  final bool isActing;
  final VoidCallback onSubmit;
  final VoidCallback onPause;
  final VoidCallback onResume;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onDuplicate;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Actions principales selon le statut
        if (campaign.isDraft) ...[
          SkyGradientButton(
            label: 'Soumettre pour révision',
            onPressed: isActing ? null : onSubmit,
            isLoading: isActing,
          ),
          const SizedBox(height: 10),
          OutlinedButton.icon(
            onPressed: isActing ? null : onEdit,
            icon: const Icon(Icons.edit_outlined, size: 16),
            label: const Text('Modifier le brouillon'),
          ),
          const SizedBox(height: 10),
          _DangerButton(
            label: 'Supprimer',
            icon: Icons.delete_outline_rounded,
            onPressed: isActing ? null : onDelete,
          ),
        ],

        if (campaign.isActive) ...[
          _ActionButton(
            label: 'Mettre en pause',
            icon: Icons.pause_circle_outline_rounded,
            color: AppColors.warn,
            onPressed: isActing ? null : onPause,
            isLoading: isActing,
          ),
        ],

        if (campaign.isPaused) ...[
          SkyGradientButton(
            label: 'Reprendre la campagne',
            onPressed: isActing ? null : onResume,
            isLoading: isActing,
          ),
        ],

        if (campaign.isRejected) ...[
          OutlinedButton.icon(
            onPressed: isActing ? null : onEdit,
            icon: const Icon(Icons.edit_outlined, size: 16),
            label: const Text('Modifier et soumettre à nouveau'),
          ),
        ],

        // Dupliquer toujours disponible (sauf brouillon déjà couverts)
        if (!campaign.isDraft) ...[
          const SizedBox(height: 10),
          OutlinedButton.icon(
            onPressed: isActing ? null : onDuplicate,
            icon: const Icon(Icons.copy_rounded, size: 16),
            label: const Text('Dupliquer en brouillon'),
          ),
        ],

        // Message d'attente
        if (campaign.isPendingReview)
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.warnLight,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.warn.withAlpha(60)),
            ),
            child: Row(
              children: [
                const Icon(Icons.hourglass_top_rounded,
                    color: AppColors.warn, size: 18),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'Votre campagne est en attente de validation par notre équipe. Vous serez notifié dès qu\'elle sera traitée.',
                    style: GoogleFonts.nunito(
                      fontSize: 12,
                      color: AppColors.warn,
                      height: 1.5,
                    ),
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.onPressed,
    this.isLoading = false,
  });

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback? onPressed;
  final bool isLoading;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onPressed,
      child: Container(
        height: 44,
        decoration: BoxDecoration(
          color: color.withAlpha(20),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withAlpha(80)),
        ),
        child: Center(
          child: isLoading
              ? SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: color,
                  ),
                )
              : Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(icon, color: color, size: 18),
                    const SizedBox(width: 8),
                    Text(
                      label,
                      style: GoogleFonts.nunito(
                        color: color,
                        fontWeight: FontWeight.w700,
                        fontSize: 15,
                      ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}

class _DangerButton extends StatelessWidget {
  const _DangerButton({
    required this.label,
    required this.icon,
    required this.onPressed,
  });

  final String label;
  final IconData icon;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return _ActionButton(
      label: label,
      icon: icon,
      color: AppColors.danger,
      onPressed: onPressed,
    );
  }
}

// ---------------------------------------------------------------------------
// États de chargement / erreur
// ---------------------------------------------------------------------------

class _LoadingState extends StatelessWidget {
  const _LoadingState();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: AppColors.bg,
      body: Center(
        child: CircularProgressIndicator(color: AppColors.sky),
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({
    required this.message,
    required this.onRetry,
    required this.onBack,
  });

  final String message;
  final VoidCallback onRetry;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Align(
                alignment: Alignment.centerLeft,
                child: GestureDetector(
                  onTap: onBack,
                  child: Container(
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(
                      color: AppColors.border,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.arrow_back_ios_new_rounded,
                        color: AppColors.navy, size: 15),
                  ),
                ),
              ),
            ),
            Expanded(
              child: Center(
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
              ),
            ),
          ],
        ),
      ),
    );
  }
}
