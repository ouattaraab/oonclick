import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/campaign_model.dart';

/// Card widget for a single campaign in the feed list.
///
/// White card with colored emoji icon (38×38 rounded), brand label (muted),
/// title (navy 11.5px), duration chip (skyPale), earn badge (sky gradient).
class CampaignCard extends StatelessWidget {
  const CampaignCard({
    super.key,
    required this.campaign,
    required this.onTap,
  });

  final CampaignModel campaign;
  final VoidCallback onTap;

  String _formatLabel(String format) {
    return switch (format) {
      'video' => 'Vidéo',
      'image' => 'Image',
      'scratch' => 'Grattage',
      'quiz' => 'Quiz',
      'flash' => 'Flash',
      _ => format,
    };
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: AppColors.sky.withAlpha(15),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Emoji icon background
            _CampaignIcon(format: campaign.format),
            const SizedBox(width: 12),

            // Content
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Format label as brand
                  Text(
                    _formatLabel(campaign.format).toUpperCase(),
                    style: GoogleFonts.nunito(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: AppColors.muted,
                      letterSpacing: 0.8,
                    ),
                  ),
                  const SizedBox(height: 3),

                  // Title
                  Text(
                    campaign.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.nunito(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w700,
                      color: AppColors.navy,
                      height: 1.35,
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Bottom row: duration + earn badge
                  Row(
                    children: [
                      // Duration chip
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: AppColors.skyPale,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.timer_outlined,
                              size: 12,
                              color: AppColors.sky2,
                            ),
                            const SizedBox(width: 3),
                            Text(
                              Formatters.duration(campaign.durationSeconds),
                              style: GoogleFonts.nunito(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: AppColors.sky2,
                              ),
                            ),
                          ],
                        ),
                      ),

                      const Spacer(),

                      // Earn badge (gradient)
                      _EarnBadge(amount: campaign.amount),
                    ],
                  ),
                ],
              ),
            ),

            // Play arrow
            const SizedBox(width: 8),
            const Icon(
              Icons.play_circle_filled_rounded,
              color: AppColors.sky,
              size: 28,
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Campaign icon with emoji background
// ---------------------------------------------------------------------------

class _CampaignIcon extends StatelessWidget {
  const _CampaignIcon({required this.format});

  final String format;

  String get _emoji {
    return switch (format) {
      'video'   => '📺',
      'image'   => '🖼️',
      'scratch' => '🎰',
      'quiz'    => '📝',
      'flash'   => '⚡',
      _         => '📣',
    };
  }

  Color get _bgColor {
    return switch (format) {
      'video'   => const Color(0xFFEBF7FE),
      'image'   => const Color(0xFFD1FAE5),
      'scratch' => const Color(0xFFF3E8FF),
      'quiz'    => const Color(0xFFEEF2FF),
      'flash'   => const Color(0xFFFEF3C7),
      _         => const Color(0xFFF0F8FF),
    };
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        color: _bgColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Center(
        child: Text(
          _emoji,
          style: const TextStyle(fontSize: 22),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Earn badge with sky gradient
// ---------------------------------------------------------------------------

class _EarnBadge extends StatelessWidget {
  const _EarnBadge({required this.amount});

  final int amount;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        gradient: AppColors.skyGradient,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('💰', style: TextStyle(fontSize: 11)),
          const SizedBox(width: 4),
          Text(
            '+${Formatters.currency(amount)}',
            style: GoogleFonts.nunito(
              fontSize: 12,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}
