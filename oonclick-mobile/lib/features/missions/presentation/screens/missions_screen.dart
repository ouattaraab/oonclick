import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/config/feature_settings_provider.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/mission_model.dart';
import '../providers/mission_provider.dart';

/// Écran des missions quotidiennes.
class MissionsScreen extends ConsumerWidget {
  const MissionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isEnabled = ref.watch(isFeatureEnabledProvider('missions'));

    if (!isEnabled) {
      return Scaffold(
        backgroundColor: AppColors.bg,
        body: _TopBar(),
      );
    }

    final missionsAsync = ref.watch(missionsProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _TopBar(),
          Expanded(
            child: missionsAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _ErrorView(
                message: err.toString(),
                onRetry: () =>
                    ref.read(missionsProvider.notifier).refresh(),
              ),
              data: (missions) => missions.isEmpty
                  ? _EmptyView()
                  : RefreshIndicator(
                      color: AppColors.sky,
                      onRefresh: () =>
                          ref.read(missionsProvider.notifier).refresh(),
                      child: ListView(
                        padding: const EdgeInsets.fromLTRB(16, 20, 16, 40),
                        children: [
                          _MissionsHeader(missions: missions),
                          const SizedBox(height: 16),
                          ...missions.map(
                            (m) => _MissionCard(
                              mission: m,
                              onClaim: () => _handleClaim(context, ref, m),
                            ),
                          ),
                        ],
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _handleClaim(
    BuildContext context,
    WidgetRef ref,
    MissionModel mission,
  ) async {
    try {
      final result =
          await ref.read(missionsProvider.notifier).claimReward(mission.id);
      if (context.mounted) {
        _showClaimDialog(context, result);
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

  void _showClaimDialog(BuildContext context, MissionClaimResult result) {
    showDialog<void>(
      context: context,
      builder: (_) => _ClaimSuccessDialog(result: result),
    );
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
            'Missions du jour',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          const Icon(Icons.emoji_events_rounded,
              color: Colors.white, size: 22),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Missions header (summary progress)
// ---------------------------------------------------------------------------

class _MissionsHeader extends StatelessWidget {
  const _MissionsHeader({required this.missions});

  final List<MissionModel> missions;

  @override
  Widget build(BuildContext context) {
    final completedCount = missions.where((m) => m.completed).length;
    final totalRewardFcfa = missions.fold<int>(
      0,
      (sum, m) => sum + (m.rewarded ? 0 : (m.completed ? m.rewardFcfa : 0)),
    );

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: AppColors.skyGradientDiagonal,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Progression aujourd\'hui',
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: Colors.white.withAlpha(220),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Text(
                '$completedCount/${missions.length}',
                style: GoogleFonts.nunito(
                  fontSize: 28,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                ),
              ),
              const SizedBox(width: 8),
              Text(
                'missions complétées',
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  color: Colors.white.withAlpha(200),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          // Progress bar
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: missions.isEmpty
                  ? 0
                  : completedCount / missions.length,
              minHeight: 8,
              backgroundColor: Colors.white.withAlpha(40),
              valueColor:
                  const AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ),
          if (totalRewardFcfa > 0) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(25),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                '${Formatters.currency(totalRewardFcfa)} à réclamer',
                style: GoogleFonts.nunito(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Mission card
// ---------------------------------------------------------------------------

class _MissionCard extends StatelessWidget {
  const _MissionCard({
    required this.mission,
    required this.onClaim,
  });

  final MissionModel mission;
  final VoidCallback onClaim;

  IconData get _icon {
    return switch (mission.type) {
      'views' => Icons.play_circle_rounded,
      'checkin' => Icons.calendar_today_rounded,
      'referral' => Icons.people_rounded,
      'survey' => Icons.assignment_rounded,
      _ => Icons.star_rounded,
    };
  }

  @override
  Widget build(BuildContext context) {
    final isCompleted = mission.completed;
    final isRewarded = mission.rewarded;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(
          color: isCompleted && !isRewarded
              ? AppColors.success.withAlpha(100)
              : AppColors.border,
          width: isCompleted && !isRewarded ? 1.5 : 1,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(8),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Icon
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: isRewarded
                      ? AppColors.successLight
                      : isCompleted
                          ? AppColors.successLight
                          : AppColors.skyPale,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  _icon,
                  color: isRewarded
                      ? AppColors.success
                      : isCompleted
                          ? AppColors.success
                          : AppColors.sky,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),

              // Title & description
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      mission.title,
                      style: GoogleFonts.nunito(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppColors.navy,
                      ),
                    ),
                    if (mission.description != null)
                      Text(
                        mission.description!,
                        style: GoogleFonts.nunito(
                          fontSize: 11,
                          color: AppColors.muted,
                        ),
                      ),
                  ],
                ),
              ),

              const SizedBox(width: 8),

              // Claim / done indicator
              if (isRewarded)
                Container(
                  width: 32,
                  height: 32,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    color: AppColors.successLight,
                  ),
                  child: const Icon(Icons.check_rounded,
                      color: AppColors.success, size: 18),
                )
              else if (isCompleted)
                GestureDetector(
                  onTap: onClaim,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF16A34A), Color(0xFF059669)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      'Réclamer',
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
            ],
          ),

          const SizedBox(height: 12),

          // Progress bar
          Row(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: mission.progressPercent,
                    minHeight: 6,
                    backgroundColor: AppColors.border,
                    valueColor: AlwaysStoppedAnimation<Color>(
                      isRewarded
                          ? AppColors.success
                          : isCompleted
                              ? AppColors.success
                              : AppColors.sky,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                '${mission.currentProgress}/${mission.target}',
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: AppColors.muted,
                ),
              ),
            ],
          ),

          const SizedBox(height: 10),

          // Rewards row
          Row(
            children: [
              if (mission.rewardFcfa > 0) ...[
                _RewardTag(
                  label: Formatters.currency(mission.rewardFcfa),
                  color: AppColors.warn,
                  bgColor: AppColors.warnLight,
                ),
                const SizedBox(width: 8),
              ],
              if (mission.rewardXp > 0)
                _RewardTag(
                  label: '+${mission.rewardXp} XP',
                  color: const Color(0xFF7C3AED),
                  bgColor: const Color(0xFFF3E8FF),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _RewardTag extends StatelessWidget {
  const _RewardTag({
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
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: GoogleFonts.nunito(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Empty view
// ---------------------------------------------------------------------------

class _EmptyView extends StatelessWidget {
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
              child: const Icon(Icons.emoji_events_outlined,
                  size: 44, color: AppColors.sky),
            ),
            const SizedBox(height: 16),
            Text(
              'Aucune mission pour aujourd\'hui',
              style: GoogleFonts.nunito(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Les missions quotidiennes seront bientôt disponibles.',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 13,
                color: AppColors.muted,
              ),
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
// Claim success dialog
// ---------------------------------------------------------------------------

class _ClaimSuccessDialog extends StatelessWidget {
  const _ClaimSuccessDialog({required this.result});

  final MissionClaimResult result;

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
                gradient: LinearGradient(
                  colors: [Color(0xFF16A34A), Color(0xFF059669)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: const Icon(Icons.emoji_events_rounded,
                  color: Colors.white, size: 40),
            ),
            const SizedBox(height: 16),
            Text(
              'Mission accomplie !',
              style: GoogleFonts.nunito(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              result.missionTitle,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 16),

            // Rewards
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (result.rewardFcfa > 0)
                  _RewardChip(
                    label: '+${Formatters.currency(result.rewardFcfa)}',
                    icon: Icons.account_balance_wallet_rounded,
                    color: AppColors.warn,
                    bgColor: AppColors.warnLight,
                  ),
                if (result.rewardFcfa > 0 && result.rewardXp > 0)
                  const SizedBox(width: 10),
                if (result.rewardXp > 0)
                  _RewardChip(
                    label: '+${result.rewardXp} XP',
                    icon: Icons.star_rounded,
                    color: const Color(0xFF7C3AED),
                    bgColor: const Color(0xFFF3E8FF),
                  ),
              ],
            ),

            const SizedBox(height: 24),
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

class _RewardChip extends StatelessWidget {
  const _RewardChip({
    required this.label,
    required this.icon,
    required this.color,
    required this.bgColor,
  });

  final String label;
  final IconData icon;
  final Color color;
  final Color bgColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 16),
          const SizedBox(width: 6),
          Text(
            label,
            style: GoogleFonts.nunito(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}
