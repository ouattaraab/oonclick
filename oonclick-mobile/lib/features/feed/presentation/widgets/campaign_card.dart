import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/campaign_model.dart';

/// Card widget that represents a single campaign in the feed list.
///
/// Shows thumbnail with gradient overlay, format badge, title and
/// the FCFA amount the user will earn.
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
        decoration: BoxDecoration(
          color: AppTheme.bgCard,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppTheme.divider),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(10),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Thumbnail
            _ThumbnailSection(campaign: campaign),

            // Info row
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    campaign.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                      color: AppTheme.textPrimary,
                      height: 1.3,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      // Duration chip
                      _InfoChip(
                        icon: Icons.timer_outlined,
                        label: Formatters.duration(
                            campaign.durationSeconds),
                        color: AppTheme.textSecondary,
                      ),
                      const Spacer(),
                      // Earn badge
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 5),
                        decoration: BoxDecoration(
                          color: AppTheme.successLight,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.monetization_on_rounded,
                              size: 14,
                              color: AppTheme.success,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              Formatters.currency(campaign.amount),
                              style: const TextStyle(
                                color: AppTheme.success,
                                fontWeight: FontWeight.w800,
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
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
// Thumbnail with gradient overlay and format badge
// ---------------------------------------------------------------------------

class _ThumbnailSection extends StatelessWidget {
  const _ThumbnailSection({required this.campaign});

  final CampaignModel campaign;

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 16 / 9,
      child: Stack(
        fit: StackFit.expand,
        children: [
          // Background image
          if (campaign.thumbnailUrl != null)
            CachedNetworkImage(
              imageUrl: campaign.thumbnailUrl!,
              fit: BoxFit.cover,
              placeholder: (ctx, prog) => Container(
                color: AppTheme.bgPage,
                child: const Center(
                  child: Icon(
                    Icons.image_outlined,
                    color: AppTheme.textHint,
                    size: 40,
                  ),
                ),
              ),
              errorWidget: (ctx, url, err) => _ThumbnailFallback(
                  format: campaign.format),
            )
          else
            _ThumbnailFallback(format: campaign.format),

          // Bottom gradient
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  stops: const [0.5, 1.0],
                  colors: [
                    Colors.transparent,
                    Colors.black.withAlpha(160),
                  ],
                ),
              ),
            ),
          ),

          // Play icon overlay
          Center(
            child: Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(200),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.play_arrow_rounded,
                color: AppTheme.primary,
                size: 30,
              ),
            ),
          ),

          // Format badge (top-left)
          Positioned(
            top: 10,
            left: 10,
            child: _FormatBadge(format: campaign.format),
          ),
        ],
      ),
    );
  }
}

class _ThumbnailFallback extends StatelessWidget {
  const _ThumbnailFallback({required this.format});

  final String format;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: _formatBgColor(format),
      child: Center(
        child: Icon(
          _formatIcon(format),
          size: 48,
          color: Colors.white.withAlpha(180),
        ),
      ),
    );
  }

  Color _formatBgColor(String format) {
    return switch (format) {
      'scratch' => const Color(0xFF8B5CF6),
      'quiz' => const Color(0xFF3B82F6),
      'flash' => const Color(0xFFF59E0B),
      _ => const Color(0xFF374151),
    };
  }

  IconData _formatIcon(String format) {
    return switch (format) {
      'scratch' => Icons.back_hand_outlined,
      'quiz' => Icons.quiz_outlined,
      'flash' => Icons.bolt_rounded,
      _ => Icons.play_circle_outline_rounded,
    };
  }
}

// ---------------------------------------------------------------------------
// Format badge
// ---------------------------------------------------------------------------

class _FormatBadge extends StatelessWidget {
  const _FormatBadge({required this.format});

  final String format;

  static const _labels = {
    'video': 'Vidéo',
    'scratch': 'Grattage',
    'quiz': 'Quiz',
    'flash': 'Flash',
  };

  static const _colors = {
    'video': Color(0xFF374151),
    'scratch': Color(0xFF8B5CF6),
    'quiz': Color(0xFF3B82F6),
    'flash': Color(0xFFF59E0B),
  };

  @override
  Widget build(BuildContext context) {
    final color =
        _colors[format] ?? const Color(0xFF374151);
    final label = _labels[format] ?? format;

    return Container(
      padding:
          const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.3,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Small info chip
// ---------------------------------------------------------------------------

class _InfoChip extends StatelessWidget {
  const _InfoChip({
    required this.icon,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: color,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}
