import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

import '../../../../core/theme/app_colors.dart';
import '../../data/models/coupon_model.dart';

/// Carte d'affichage d'un coupon collecté avec affichage QR/code et actions.
class CouponCard extends StatelessWidget {
  const CouponCard({
    super.key,
    required this.userCoupon,
    required this.onUse,
  });

  final UserCouponModel userCoupon;
  final VoidCallback onUse;

  @override
  Widget build(BuildContext context) {
    final coupon = userCoupon.coupon;
    if (coupon == null) return const SizedBox.shrink();

    final isAvailable = userCoupon.isAvailable;
    final isUsed = userCoupon.isUsed;
    final isExpired = coupon.isExpired;

    Color borderColor = isUsed
        ? AppColors.border
        : isExpired
            ? AppColors.dangerLight
            : AppColors.skyMid;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor, width: isAvailable ? 1.5 : 1),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(6),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          // Header row
          Container(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Discount badge
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    gradient: isUsed || isExpired
                        ? const LinearGradient(
                            colors: [Color(0xFFCBD5E1), Color(0xFF94A3B8)])
                        : AppColors.skyGradientDiagonal,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        coupon.isPercent
                            ? '-${coupon.discountValue}%'
                            : '-${coupon.discountValue}',
                        style: GoogleFonts.nunito(
                          fontSize: coupon.isPercent ? 14 : 11,
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      if (!coupon.isPercent)
                        Text(
                          'FCFA',
                          style: GoogleFonts.nunito(
                            fontSize: 9,
                            fontWeight: FontWeight.w600,
                            color: Colors.white.withAlpha(200),
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        coupon.partnerName,
                        style: GoogleFonts.nunito(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: isUsed ? AppColors.muted : AppColors.navy,
                        ),
                      ),
                      if (coupon.description != null) ...[
                        const SizedBox(height: 3),
                        Text(
                          coupon.description!,
                          style: GoogleFonts.nunito(
                            fontSize: 11,
                            color: AppColors.muted,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      const SizedBox(height: 6),
                      // Status chips
                      Row(
                        children: [
                          if (isUsed)
                            _StatusChip(
                              label: 'Utilisé',
                              color: AppColors.muted,
                              bgColor: AppColors.border,
                            )
                          else if (isExpired)
                            _StatusChip(
                              label: 'Expiré',
                              color: AppColors.danger,
                              bgColor: AppColors.dangerLight,
                            )
                          else
                            _StatusChip(
                              label: 'Disponible',
                              color: AppColors.success,
                              bgColor: AppColors.successLight,
                            ),
                          if (coupon.expiresAt != null && !isUsed) ...[
                            const SizedBox(width: 6),
                            Text(
                              'Exp. ${DateFormat('dd/MM/yy').format(coupon.expiresAt!)}',
                              style: GoogleFonts.nunito(
                                fontSize: 10,
                                color: isExpired
                                    ? AppColors.danger
                                    : AppColors.muted,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Divider with dashed effect
          Row(
            children: [
              const SizedBox(width: 16),
              Expanded(
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    final dashWidth = 6.0;
                    final dashSpace = 4.0;
                    final dashCount =
                        (constraints.maxWidth / (dashWidth + dashSpace))
                            .floor();
                    return Row(
                      children: List.generate(
                        dashCount,
                        (_) => Container(
                          width: dashWidth,
                          height: 1,
                          margin: EdgeInsets.only(right: dashSpace),
                          color: AppColors.border,
                        ),
                      ),
                    );
                  },
                ),
              ),
              const SizedBox(width: 16),
            ],
          ),

          // Code + action row
          Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                // QR-style code chip
                Expanded(
                  child: GestureDetector(
                    onTap: isAvailable
                        ? () {
                            Clipboard.setData(
                                ClipboardData(text: coupon.code));
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(
                                  'Code copié : ${coupon.code}',
                                  style: GoogleFonts.nunito(),
                                ),
                                duration: const Duration(seconds: 2),
                                backgroundColor: AppColors.success,
                              ),
                            );
                          }
                        : null,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: isAvailable
                            ? AppColors.skyPale
                            : AppColors.bg,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color:
                              isAvailable ? AppColors.skyMid : AppColors.border,
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.qr_code_rounded,
                            size: 16,
                            color: isAvailable ? AppColors.sky : AppColors.muted,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            coupon.code,
                            style: GoogleFonts.spaceMono(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color:
                                  isAvailable ? AppColors.navy : AppColors.muted,
                              letterSpacing: 1.5,
                            ),
                          ),
                          if (isAvailable) ...[
                            const SizedBox(width: 8),
                            const Icon(Icons.copy_rounded,
                                size: 13, color: AppColors.sky),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
                if (isAvailable) ...[
                  const SizedBox(width: 10),
                  GestureDetector(
                    onTap: onUse,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 10),
                      decoration: BoxDecoration(
                        gradient: AppColors.skyGradientDiagonal,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        'Utiliser',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({
    required this.label,
    required this.color,
    required this.bgColor,
  });

  final String label;
  final Color color;
  final Color bgColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: GoogleFonts.nunito(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}
