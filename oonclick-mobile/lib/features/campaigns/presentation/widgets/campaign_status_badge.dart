import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../data/models/campaign_model.dart';

/// Badge coloré affichant le statut d'une campagne en français.
class CampaignStatusBadge extends StatelessWidget {
  const CampaignStatusBadge({
    super.key,
    required this.status,
    this.large = false,
  });

  final CampaignStatus status;
  final bool large;

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = _colors(status);
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: large ? 12 : 8,
        vertical: large ? 5 : 3,
      ),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: large ? 8 : 6,
            height: large ? 8 : 6,
            decoration: BoxDecoration(
              color: fg,
              shape: BoxShape.circle,
            ),
          ),
          SizedBox(width: large ? 6 : 4),
          Text(
            status.label,
            style: GoogleFonts.nunito(
              fontSize: large ? 13 : 11,
              fontWeight: FontWeight.w700,
              color: fg,
            ),
          ),
        ],
      ),
    );
  }

  static (Color bg, Color fg) _colors(CampaignStatus status) {
    return switch (status) {
      CampaignStatus.draft => (
          const Color(0xFFF1F5F9),
          AppColors.muted,
        ),
      CampaignStatus.pendingReview => (
          AppColors.warnLight,
          AppColors.warn,
        ),
      CampaignStatus.approved => (
          AppColors.skyPale,
          AppColors.sky2,
        ),
      CampaignStatus.active => (
          AppColors.successLight,
          AppColors.success,
        ),
      CampaignStatus.paused => (
          AppColors.warnLight,
          AppColors.warn,
        ),
      CampaignStatus.completed => (
          const Color(0xFFF1F5F9),
          AppColors.muted,
        ),
      CampaignStatus.rejected => (
          AppColors.dangerLight,
          AppColors.danger,
        ),
    };
  }
}
