import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../data/models/campaign_model.dart';

/// Petit badge affichant le format d'une campagne.
class CampaignFormatBadge extends StatelessWidget {
  const CampaignFormatBadge({
    super.key,
    required this.format,
  });

  final CampaignFormat format;

  @override
  Widget build(BuildContext context) {
    final (icon, color, bg) = _style(format);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 4),
          Text(
            format.label,
            style: GoogleFonts.nunito(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  static (IconData icon, Color color, Color bg) _style(CampaignFormat format) {
    return switch (format) {
      CampaignFormat.video => (
          Icons.play_circle_outline_rounded,
          AppColors.sky2,
          AppColors.skyPale,
        ),
      CampaignFormat.scratch => (
          Icons.auto_awesome_rounded,
          const Color(0xFF7C3AED),
          const Color(0xFFF5F3FF),
        ),
      CampaignFormat.quiz => (
          Icons.quiz_rounded,
          AppColors.warn,
          AppColors.warnLight,
        ),
      CampaignFormat.flash => (
          Icons.bolt_rounded,
          const Color(0xFFD97706),
          const Color(0xFFFEF3C7),
        ),
    };
  }
}
