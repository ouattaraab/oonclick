import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/checkin_model.dart';
import '../providers/checkin_provider.dart';

/// Écran de check-in quotidien.
class CheckinScreen extends ConsumerStatefulWidget {
  const CheckinScreen({super.key});

  @override
  ConsumerState<CheckinScreen> createState() => _CheckinScreenState();
}

class _CheckinScreenState extends ConsumerState<CheckinScreen>
    with TickerProviderStateMixin {
  bool _isCheckinLoading = false;
  CheckinResultModel? _lastResult;

  // Animation contrôleur pour les confettis.
  late final AnimationController _confettiController;
  late final AnimationController _pulseController;
  late final Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();
    _confettiController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    );
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat(reverse: true);
    _pulseAnimation = Tween<double>(begin: 0.95, end: 1.05).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _confettiController.dispose();
    _pulseController.dispose();
    super.dispose();
  }

  Future<void> _doCheckin() async {
    setState(() => _isCheckinLoading = true);
    try {
      final result =
          await ref.read(checkinProvider.notifier).performCheckin();
      setState(() => _lastResult = result);
      _confettiController.forward(from: 0);

      if (mounted) {
        _showSuccessDialog(result);
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
      if (mounted) setState(() => _isCheckinLoading = false);
    }
  }

  void _showSuccessDialog(CheckinResultModel result) {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _CheckinSuccessDialog(result: result),
    );
  }

  @override
  Widget build(BuildContext context) {
    final checkinAsync = ref.watch(checkinProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _CheckinTopBar(),
          Expanded(
            child: checkinAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _CheckinError(
                message: err.toString(),
                onRetry: () => ref.read(checkinProvider.notifier).refresh(),
              ),
              data: (status) => RefreshIndicator(
                color: AppColors.sky,
                onRefresh: () =>
                    ref.read(checkinProvider.notifier).refresh(),
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 20, 16, 40),
                  children: [
                    // Streak circle
                    _StreakCircle(
                      streak: status.currentStreak,
                      checkedInToday: status.checkedInToday,
                      pulseAnimation: _pulseAnimation,
                    ),
                    const SizedBox(height: 24),

                    // Bonus display
                    _BonusCard(status: status),
                    const SizedBox(height: 20),

                    // Check-in button
                    _CheckinButton(
                      checkedInToday: status.checkedInToday,
                      isLoading: _isCheckinLoading,
                      onPressed: status.checkedInToday ? null : _doCheckin,
                    ),
                    const SizedBox(height: 28),

                    // 7-day streak calendar
                    _StreakCalendar(
                      streak: status.currentStreak,
                      history: status.checkinHistory,
                      checkedInToday: status.checkedInToday,
                    ),
                    const SizedBox(height: 20),

                    // Stats card
                    _StatsCard(status: status),
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

class _CheckinTopBar extends StatelessWidget {
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
            'Check-in quotidien',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          const Text('🔥', style: TextStyle(fontSize: 22)),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Streak circle
// ---------------------------------------------------------------------------

class _StreakCircle extends StatelessWidget {
  const _StreakCircle({
    required this.streak,
    required this.checkedInToday,
    required this.pulseAnimation,
  });

  final int streak;
  final bool checkedInToday;
  final Animation<double> pulseAnimation;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        children: [
          ScaleTransition(
            scale: checkedInToday
                ? const AlwaysStoppedAnimation(1.0)
                : pulseAnimation,
            child: Container(
              width: 160,
              height: 160,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: checkedInToday
                    ? const LinearGradient(
                        colors: [Color(0xFF16A34A), Color(0xFF059669)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      )
                    : AppColors.skyGradientDiagonal,
                boxShadow: [
                  BoxShadow(
                    color: (checkedInToday ? AppColors.success : AppColors.sky)
                        .withAlpha(80),
                    blurRadius: 30,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    '$streak',
                    style: GoogleFonts.nunito(
                      fontSize: 56,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                      height: 1,
                    ),
                  ),
                  Text(
                    streak == 1 ? 'jour' : 'jours',
                    style: GoogleFonts.nunito(
                      fontSize: 14,
                      color: Colors.white.withAlpha(220),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    checkedInToday ? 'Effectué !' : 'de suite',
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      color: Colors.white.withAlpha(180),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            checkedInToday
                ? 'Revenez demain pour continuer votre streak !'
                : 'Faites votre check-in pour gagner des bonus !',
            textAlign: TextAlign.center,
            style: GoogleFonts.nunito(
              fontSize: 13,
              color: AppColors.muted,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Bonus card
// ---------------------------------------------------------------------------

class _BonusCard extends StatelessWidget {
  const _BonusCard({required this.status});

  final CheckinStatusModel status;

  int get _bonusForStreak {
    if (status.bonusForToday > 0) return status.bonusForToday;
    final streak = status.currentStreak + (status.checkedInToday ? 0 : 1);
    if (streak >= 30) return 500;
    if (streak >= 14) return 300;
    if (streak >= 7) return 200;
    if (streak >= 3) return 150;
    return 100;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(15),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.warnLight,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.star_rounded,
                color: AppColors.warn, size: 28),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  status.checkedInToday
                      ? 'Bonus d\'aujourd\'hui'
                      : 'Bonus disponible',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    color: AppColors.muted,
                  ),
                ),
                Text(
                  Formatters.currency(_bonusForStreak),
                  style: GoogleFonts.nunito(
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    color: AppColors.warn,
                  ),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                'Total gagné',
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  color: AppColors.muted,
                ),
              ),
              Text(
                Formatters.currency(status.totalBonusEarned),
                style: GoogleFonts.nunito(
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                  color: AppColors.navy,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Check-in button
// ---------------------------------------------------------------------------

class _CheckinButton extends StatelessWidget {
  const _CheckinButton({
    required this.checkedInToday,
    required this.isLoading,
    required this.onPressed,
  });

  final bool checkedInToday;
  final bool isLoading;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    if (checkedInToday) {
      return Container(
        width: double.infinity,
        height: 52,
        decoration: BoxDecoration(
          color: AppColors.successLight,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.success.withAlpha(80)),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.check_circle_rounded,
                color: AppColors.success, size: 22),
            const SizedBox(width: 8),
            Text(
              'Check-in effectué aujourd\'hui',
              style: GoogleFonts.nunito(
                fontWeight: FontWeight.w700,
                fontSize: 15,
                color: AppColors.success,
              ),
            ),
          ],
        ),
      );
    }

    return SkyGradientButton(
      label: 'Check-in aujourd\'hui',
      onPressed: onPressed,
      isLoading: isLoading,
      height: 52,
      borderRadius: 14,
    );
  }
}

// ---------------------------------------------------------------------------
// 7-day streak calendar
// ---------------------------------------------------------------------------

class _StreakCalendar extends StatelessWidget {
  const _StreakCalendar({
    required this.streak,
    required this.history,
    required this.checkedInToday,
  });

  final int streak;
  final List<String> history;
  final bool checkedInToday;

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final days = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

    // Build the 7 days centered on today.
    final dayList = List.generate(7, (i) {
      final day = now.subtract(Duration(days: 6 - i));
      final dateStr =
          '${day.year}-${day.month.toString().padLeft(2, '0')}-${day.day.toString().padLeft(2, '0')}';
      final isToday = i == 6;
      final checked = history.contains(dateStr) ||
          (isToday && checkedInToday);
      return (day: day, checked: checked, isToday: isToday);
    });

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Activité — 7 derniers jours',
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppColors.navy,
            ),
          ),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: dayList.map((item) {
              final weekday = item.day.weekday - 1;
              return _DayCircle(
                label: days[weekday],
                checked: item.checked,
                isToday: item.isToday,
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}

class _DayCircle extends StatelessWidget {
  const _DayCircle({
    required this.label,
    required this.checked,
    required this.isToday,
  });

  final String label;
  final bool checked;
  final bool isToday;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          label,
          style: GoogleFonts.nunito(
            fontSize: 11,
            color: AppColors.muted,
            fontWeight: isToday ? FontWeight.w700 : FontWeight.w500,
          ),
        ),
        const SizedBox(height: 6),
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: checked ? AppColors.skyGradient : null,
            color: checked ? null : AppColors.bg,
            border: Border.all(
              color: isToday && !checked
                  ? AppColors.sky
                  : checked
                      ? Colors.transparent
                      : AppColors.border,
              width: isToday ? 2 : 1,
            ),
          ),
          child: checked
              ? const Icon(Icons.check_rounded, color: Colors.white, size: 18)
              : isToday
                  ? const Icon(Icons.circle, color: AppColors.sky, size: 10)
                  : null,
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Stats card
// ---------------------------------------------------------------------------

class _StatsCard extends StatelessWidget {
  const _StatsCard({required this.status});

  final CheckinStatusModel status;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Mes statistiques',
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppColors.navy,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              _StatItem(
                icon: Icons.calendar_today_rounded,
                label: 'Total check-ins',
                value: '${status.totalCheckins}',
              ),
              _StatItem(
                icon: Icons.local_fire_department_rounded,
                label: 'Meilleur streak',
                value: '${status.currentStreak} j',
                iconColor: AppColors.danger,
              ),
              _StatItem(
                icon: Icons.account_balance_wallet_rounded,
                label: 'Gains bonus',
                value: Formatters.currency(status.totalBonusEarned),
                iconColor: AppColors.warn,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  const _StatItem({
    required this.icon,
    required this.label,
    required this.value,
    this.iconColor = AppColors.sky,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color iconColor;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, color: iconColor, size: 22),
          const SizedBox(height: 6),
          Text(
            value,
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: AppColors.navy,
            ),
          ),
          Text(
            label,
            textAlign: TextAlign.center,
            style: GoogleFonts.nunito(
              fontSize: 10,
              color: AppColors.muted,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Success dialog
// ---------------------------------------------------------------------------

class _CheckinSuccessDialog extends StatelessWidget {
  const _CheckinSuccessDialog({required this.result});

  final CheckinResultModel result;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: AppColors.skyGradientDiagonal,
              ),
              child: const Icon(Icons.star_rounded, color: Colors.white, size: 40),
            ),
            const SizedBox(height: 16),
            Text(
              'Check-in réussi !',
              style: GoogleFonts.nunito(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              result.message,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 14,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              decoration: BoxDecoration(
                gradient: AppColors.skyGradient,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    '+${Formatters.currency(result.bonusAmount)}',
                    style: GoogleFonts.nunito(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'ajoutés au wallet',
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      color: Colors.white.withAlpha(220),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Streak : Jour ${result.streakDay}',
              style: GoogleFonts.nunito(
                fontSize: 13,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 20),
            SkyGradientButton(
              label: 'Super, merci !',
              onPressed: () => Navigator.of(context).pop(),
              height: 46,
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Error widget
// ---------------------------------------------------------------------------

class _CheckinError extends StatelessWidget {
  const _CheckinError({required this.message, required this.onRetry});

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
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(color: AppColors.muted),
            ),
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
