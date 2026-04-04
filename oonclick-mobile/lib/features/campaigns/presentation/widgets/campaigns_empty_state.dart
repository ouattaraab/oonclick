import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';

/// État vide affiché quand aucune campagne ne correspond au filtre actif.
class CampaignsEmptyState extends StatelessWidget {
  const CampaignsEmptyState({
    super.key,
    required this.onCreateTap,
    this.filtered = false,
  });

  final VoidCallback onCreateTap;

  /// Vrai si un filtre de statut est actif (pas de campagne pour ce filtre).
  final bool filtered;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 48),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                gradient: AppColors.skyGradientDiagonal,
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.campaign_outlined,
                size: 40,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 20),
            Text(
              filtered
                  ? 'Aucune campagne\npour ce filtre'
                  : 'Aucune campagne\npour le moment',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              filtered
                  ? 'Essayez un autre filtre ou créez\nune nouvelle campagne.'
                  : 'Lancez votre première campagne\net touchez des milliers d\'utilisateurs.',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 13,
                color: AppColors.muted,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 24),
            SkyGradientButton(
              label: 'Créer une campagne',
              onPressed: onCreateTap,
              width: 200,
            ),
          ],
        ),
      ),
    );
  }
}
