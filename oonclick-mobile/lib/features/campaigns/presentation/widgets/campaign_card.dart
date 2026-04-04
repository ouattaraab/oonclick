import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/campaign_model.dart';
import 'campaign_status_badge.dart';
import 'campaign_format_badge.dart';

/// Carte affichée dans la liste des campagnes.
/// Les campagnes actives affichent un point vert animé pour signaler leur
/// diffusion en temps réel et une bordure verte subtile.
class CampaignCard extends StatelessWidget {
  const CampaignCard({
    super.key,
    required this.campaign,
    required this.onTap,
  });

  final CampaignModel campaign;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: campaign.isActive
                ? AppColors.success.withAlpha(80)
                : AppColors.border,
          ),
          boxShadow: [
            BoxShadow(
              color: AppColors.navy.withAlpha(8),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Thumbnail ou placeholder
            _ThumbnailSection(campaign: campaign),

            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Titre + badges
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          campaign.title,
                          style: GoogleFonts.nunito(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            color: AppColors.navy,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (campaign.isActive) ...[
                            const _PulsingDot(),
                            const SizedBox(width: 6),
                          ],
                          CampaignStatusBadge(status: campaign.status),
                        ],
                      ),
                    ],
                  ),

                  const SizedBox(height: 8),

                  // Format + budget
                  Row(
                    children: [
                      CampaignFormatBadge(format: campaign.format),
                      const Spacer(),
                      Text(
                        Formatters.currency(campaign.budget),
                        style: GoogleFonts.nunito(
                          fontSize: 13,
                          fontWeight: FontWeight.w800,
                          color: AppColors.navy,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 10),

                  // Barre de progression des vues
                  _ViewsProgressBar(campaign: campaign),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Point vert animé — indicateur EN DIRECT dans la liste
// ---------------------------------------------------------------------------

class _PulsingDot extends StatefulWidget {
  const _PulsingDot();

  @override
  State<_PulsingDot> createState() => _PulsingDotState();
}

class _PulsingDotState extends State<_PulsingDot>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;
  late final Animation<double> _scaleAnim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    )..repeat(reverse: true);
    _scaleAnim = Tween<double>(begin: 0.7, end: 1.0).animate(
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
      animation: _scaleAnim,
      builder: (context, _) => Transform.scale(
        scale: _scaleAnim.value,
        child: Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            color: AppColors.success,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: AppColors.success.withAlpha(100),
                blurRadius: 4,
                spreadRadius: 1,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Thumbnail
// ---------------------------------------------------------------------------

class _ThumbnailSection extends StatelessWidget {
  const _ThumbnailSection({required this.campaign});
  final CampaignModel campaign;

  @override
  Widget build(BuildContext context) {
    final hasThumb =
        campaign.thumbnailUrl != null && campaign.thumbnailUrl!.isNotEmpty;
    final hasMedia =
        campaign.mediaUrl != null && campaign.mediaUrl!.isNotEmpty;
    final imageUrl = hasThumb
        ? campaign.thumbnailUrl!
        : hasMedia
            ? campaign.mediaUrl!
            : null;

    return ClipRRect(
      borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
      child: imageUrl != null
          ? Image.network(
              imageUrl,
              height: 120,
              width: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) => _PlaceholderThumb(
                format: campaign.format,
              ),
            )
          : _PlaceholderThumb(format: campaign.format),
    );
  }
}

class _PlaceholderThumb extends StatelessWidget {
  const _PlaceholderThumb({required this.format});
  final CampaignFormat format;

  @override
  Widget build(BuildContext context) {
    final (icon, color) = switch (format) {
      CampaignFormat.video => (Icons.play_circle_fill_rounded, AppColors.sky),
      CampaignFormat.scratch => (
          Icons.auto_awesome_rounded,
          const Color(0xFF7C3AED)
        ),
      CampaignFormat.quiz => (Icons.quiz_rounded, AppColors.warn),
      CampaignFormat.flash => (Icons.bolt_rounded, const Color(0xFFF59E0B)),
    };

    return Container(
      height: 120,
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [color.withAlpha(40), color.withAlpha(15)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Icon(icon, size: 40, color: color.withAlpha(160)),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Barre de progression des vues
// ---------------------------------------------------------------------------

class _ViewsProgressBar extends StatelessWidget {
  const _ViewsProgressBar({required this.campaign});
  final CampaignModel campaign;

  @override
  Widget build(BuildContext context) {
    final progress = campaign.viewsProgress;
    final pct = (progress * 100).round();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              '${Formatters.compact(campaign.viewsCount)} vues',
              style: GoogleFonts.nunito(
                fontSize: 11,
                color: AppColors.muted,
                fontWeight: FontWeight.w600,
              ),
            ),
            Text(
              '$pct% sur ${Formatters.compact(campaign.maxViews)}',
              style: GoogleFonts.nunito(
                fontSize: 11,
                color: AppColors.muted,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: 5),
        ClipRRect(
          borderRadius: BorderRadius.circular(6),
          child: LinearProgressIndicator(
            value: progress,
            backgroundColor: AppColors.border,
            valueColor: const AlwaysStoppedAnimation<Color>(AppColors.sky),
            minHeight: 6,
          ),
        ),
      ],
    );
  }
}
