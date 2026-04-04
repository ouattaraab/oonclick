import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/config/feature_settings_provider.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/models/coupon_model.dart';
import '../providers/coupon_provider.dart';
import '../widgets/coupon_card.dart';

/// Ecran des coupons collectés par l'utilisateur.
class CouponsScreen extends ConsumerWidget {
  const CouponsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isEnabled = ref.watch(isFeatureEnabledProvider('coupons'));

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _TopBar(),
          if (!isEnabled)
            Expanded(child: _FeatureDisabledView())
          else
            Expanded(child: _CouponsBody()),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Body
// ---------------------------------------------------------------------------

class _CouponsBody extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final couponsAsync = ref.watch(couponsProvider);

    return couponsAsync.when(
      loading: () =>
          const Center(child: CircularProgressIndicator(color: AppColors.sky)),
      error: (err, _) => _ErrorView(
        message: err.toString(),
        onRetry: () => ref.read(couponsProvider.notifier).refresh(),
      ),
      data: (coupons) => coupons.isEmpty
          ? _EmptyView(
              onRetry: () => ref.read(couponsProvider.notifier).refresh())
          : RefreshIndicator(
              color: AppColors.sky,
              onRefresh: () => ref.read(couponsProvider.notifier).refresh(),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 40),
                children: [
                  _CouponsHeader(coupons: coupons),
                  const SizedBox(height: 16),
                  ...coupons.map(
                    (uc) => CouponCard(
                      userCoupon: uc,
                      onUse: () => _handleUse(context, ref, uc),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Future<void> _handleUse(
    BuildContext context,
    WidgetRef ref,
    UserCouponModel userCoupon,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => _ConfirmUseDialog(coupon: userCoupon.coupon!),
    );

    if (confirmed != true) return;

    try {
      final msg =
          await ref.read(couponsProvider.notifier).markUsed(userCoupon.id);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(msg, style: GoogleFonts.nunito()),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
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
    }
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _TopBar extends StatelessWidget {
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
            'Mes coupons',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          const Icon(Icons.confirmation_num_rounded,
              color: Colors.white, size: 22),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Coupons header (summary)
// ---------------------------------------------------------------------------

class _CouponsHeader extends StatelessWidget {
  const _CouponsHeader({required this.coupons});

  final List<UserCouponModel> coupons;

  @override
  Widget build(BuildContext context) {
    final available = coupons.where((c) => c.isAvailable).length;
    final used = coupons.where((c) => c.isUsed).length;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: AppColors.skyGradientDiagonal,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Mes coupons collectés',
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: Colors.white.withAlpha(220),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${coupons.length} coupon${coupons.length > 1 ? 's' : ''}',
                  style: GoogleFonts.nunito(
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              _HeaderStat(label: 'Disponibles', value: '$available',
                  color: Colors.white),
              const SizedBox(height: 4),
              _HeaderStat(
                  label: 'Utilisés',
                  value: '$used',
                  color: Colors.white.withAlpha(180)),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeaderStat extends StatelessWidget {
  const _HeaderStat({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          value,
          style: GoogleFonts.nunito(
            fontSize: 18,
            fontWeight: FontWeight.w900,
            color: color,
          ),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: GoogleFonts.nunito(fontSize: 11, color: color),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Feature disabled
// ---------------------------------------------------------------------------

class _FeatureDisabledView extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppColors.skyPale,
                borderRadius: BorderRadius.circular(24),
              ),
              child:
                  const Icon(Icons.lock_outline, size: 40, color: AppColors.sky),
            ),
            const SizedBox(height: 16),
            Text(
              'Fonctionnalité non disponible',
              style: GoogleFonts.nunito(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Les coupons ne sont pas encore activés sur cette plateforme.',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(fontSize: 13, color: AppColors.muted),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Empty view
// ---------------------------------------------------------------------------

class _EmptyView extends StatelessWidget {
  const _EmptyView({required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppColors.skyPale,
                borderRadius: BorderRadius.circular(24),
              ),
              child: const Icon(Icons.confirmation_num_outlined,
                  size: 44, color: AppColors.sky),
            ),
            const SizedBox(height: 16),
            Text(
              'Aucun coupon pour le moment',
              style: GoogleFonts.nunito(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Regardez des publicités pour collecter automatiquement des coupons.',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(fontSize: 13, color: AppColors.muted),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: onRetry,
              child: const Text('Actualiser'),
            ),
          ],
        ),
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
                size: 48, color: AppColors.danger),
            const SizedBox(height: 12),
            Text(
              message.replaceAll('Exception: ', ''),
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(color: AppColors.muted, fontSize: 13),
            ),
            const SizedBox(height: 16),
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

// ---------------------------------------------------------------------------
// Confirm use dialog
// ---------------------------------------------------------------------------

class _ConfirmUseDialog extends StatelessWidget {
  const _ConfirmUseDialog({required this.coupon});

  final CouponModel coupon;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                gradient: AppColors.skyGradientDiagonal,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.confirmation_num_rounded,
                  color: Colors.white, size: 32),
            ),
            const SizedBox(height: 16),
            Text(
              'Utiliser ce coupon ?',
              style: GoogleFonts.nunito(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Le code ${coupon.code} (${coupon.discountLabel}) sera marqué comme utilisé et ne pourra plus être utilisé.',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(fontSize: 13, color: AppColors.muted),
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(false),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                    child: Text(
                      'Annuler',
                      style: GoogleFonts.nunito(fontWeight: FontWeight.w700),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: SkyGradientButton(
                    label: 'Confirmer',
                    onPressed: () => Navigator.of(context).pop(true),
                    height: 46,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
