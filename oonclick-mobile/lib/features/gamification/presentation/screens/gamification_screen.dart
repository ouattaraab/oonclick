import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/models/gamification_model.dart';
import '../providers/gamification_provider.dart';

/// Écran de gamification — niveau XP et grille de badges.
class GamificationScreen extends ConsumerWidget {
  const GamificationScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileAsync = ref.watch(gamificationProfileProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _GamificationTopBar(),
          Expanded(
            child: profileAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _ErrorView(
                message: err.toString(),
                onRetry: () =>
                    ref.invalidate(gamificationProfileProvider),
              ),
              data: (profile) => RefreshIndicator(
                color: AppColors.sky,
                onRefresh: () async {
                  ref.invalidate(gamificationProfileProvider);
                  ref.invalidate(badgesProvider);
                },
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 20, 16, 40),
                  children: [
                    // XP Level card
                    _LevelCard(profile: profile),
                    const SizedBox(height: 20),

                    // Quick actions row
                    _QuickRow(profile: profile),
                    const SizedBox(height: 24),

                    // Badges section
                    Row(
                      children: [
                        Text(
                          'Mes Badges',
                          style: GoogleFonts.nunito(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: AppColors.navy,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            gradient: AppColors.skyGradient,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '${profile.badgesCount}/${profile.badges.length}',
                            style: GoogleFonts.nunito(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Badges grid
                    _BadgesGrid(badges: profile.badges),
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

class _GamificationTopBar extends StatelessWidget {
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
            'Progression & Badges',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          GestureDetector(
            onTap: () => context.push('/leaderboard'),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(30),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  const Icon(Icons.leaderboard_rounded,
                      color: Colors.white, size: 14),
                  const SizedBox(width: 4),
                  Text(
                    'Classement',
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Level card with XP progress bar
// ---------------------------------------------------------------------------

class _LevelCard extends StatelessWidget {
  const _LevelCard({required this.profile});

  final GamificationProfile profile;

  String _levelLabel(int level) {
    if (level >= 50) return 'Légende';
    if (level >= 30) return 'Maître';
    if (level >= 20) return 'Expert';
    if (level >= 10) return 'Avancé';
    if (level >= 5) return 'Intermédiaire';
    return 'Débutant';
  }

  @override
  Widget build(BuildContext context) {
    final progress =
        profile.progressPercent.clamp(0.0, 1.0);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: AppColors.skyGradientDiagonal,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(60),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Level badge
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: Colors.white.withAlpha(40),
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    '${profile.level}',
                    style: GoogleFonts.nunito(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Niveau ${profile.level} — ${_levelLabel(profile.level)}',
                      style: GoogleFonts.nunito(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      '${profile.xp} XP au total',
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        color: Colors.white.withAlpha(210),
                      ),
                    ),
                  ],
                ),
              ),
              // Badges count
              Column(
                children: [
                  const Text('🏅', style: TextStyle(fontSize: 20)),
                  Text(
                    '${profile.badgesCount}',
                    style: GoogleFonts.nunito(
                      fontSize: 14,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Progress bar
          Row(
            children: [
              Text(
                'Niveau ${profile.level}',
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  color: Colors.white.withAlpha(200),
                ),
              ),
              const Spacer(),
              Text(
                '${profile.xpForNext} XP pour niveau ${profile.nextLevel}',
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  color: Colors.white.withAlpha(200),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 10,
              backgroundColor: Colors.white.withAlpha(50),
              valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '${(progress * 100).toStringAsFixed(0)}% vers le prochain niveau',
            style: GoogleFonts.nunito(
              fontSize: 11,
              color: Colors.white.withAlpha(210),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Quick row
// ---------------------------------------------------------------------------

class _QuickRow extends StatelessWidget {
  const _QuickRow({required this.profile});

  final GamificationProfile profile;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _QuickCard(
          emoji: '🏆',
          label: 'Classement',
          onTap: () => context.push('/leaderboard'),
        ),
        const SizedBox(width: 10),
        _QuickCard(
          emoji: '🔥',
          label: 'Check-in',
          onTap: () => context.push('/checkin'),
        ),
        const SizedBox(width: 10),
        _QuickCard(
          emoji: '💳',
          label: 'Wallet',
          onTap: () => context.go('/wallet'),
        ),
      ],
    );
  }
}

class _QuickCard extends StatelessWidget {
  const _QuickCard({
    required this.emoji,
    required this.label,
    required this.onTap,
  });

  final String emoji;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            color: AppColors.white,
            border: Border.all(color: AppColors.border),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            children: [
              Text(emoji, style: const TextStyle(fontSize: 22)),
              const SizedBox(height: 6),
              Text(
                label,
                style: GoogleFonts.nunito(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.navy,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Badges grid
// ---------------------------------------------------------------------------

class _BadgesGrid extends StatelessWidget {
  const _BadgesGrid({required this.badges});

  final List<BadgeModel> badges;

  @override
  Widget build(BuildContext context) {
    if (badges.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 32),
          child: Text(
            'Aucun badge disponible',
            style: GoogleFonts.nunito(color: AppColors.muted),
          ),
        ),
      );
    }

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        crossAxisSpacing: 10,
        mainAxisSpacing: 10,
        childAspectRatio: 0.85,
      ),
      itemCount: badges.length,
      itemBuilder: (context, index) {
        return _BadgeTile(badge: badges[index]);
      },
    );
  }
}

class _BadgeTile extends StatelessWidget {
  const _BadgeTile({required this.badge});

  final BadgeModel badge;

  @override
  Widget build(BuildContext context) {
    final earned = badge.earned;
    return GestureDetector(
      onTap: () => _showBadgeDetail(context, badge),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: earned ? AppColors.white : AppColors.bg,
          border: Border.all(
            color: earned ? AppColors.sky.withAlpha(80) : AppColors.border,
            width: earned ? 1.5 : 1,
          ),
          borderRadius: BorderRadius.circular(14),
          boxShadow: earned
              ? [
                  BoxShadow(
                    color: AppColors.sky.withAlpha(20),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  )
                ]
              : null,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Icon
            Opacity(
              opacity: earned ? 1.0 : 0.35,
              child: Text(
                badge.icon,
                style: const TextStyle(fontSize: 30),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              badge.displayName,
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: GoogleFonts.nunito(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: earned ? AppColors.navy : AppColors.muted,
              ),
            ),
            if (!earned) ...[
              const SizedBox(height: 4),
              Text(
                '${badge.xpRequired} XP',
                style: GoogleFonts.nunito(
                  fontSize: 10,
                  color: AppColors.muted,
                ),
              ),
            ],
            if (earned) ...[
              const SizedBox(height: 4),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  gradient: AppColors.skyGradient,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  'Obtenu',
                  style: GoogleFonts.nunito(
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _showBadgeDetail(BuildContext context, BadgeModel badge) {
    showModalBottomSheet<void>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => Padding(
        padding: const EdgeInsets.fromLTRB(24, 20, 24, 40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 20),
            Text(badge.icon, style: const TextStyle(fontSize: 48)),
            const SizedBox(height: 12),
            Text(
              badge.displayName,
              style: GoogleFonts.nunito(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              badge.description,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 14,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.skyPale,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    badge.category,
                    style: GoogleFonts.nunito(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.sky,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: badge.earned
                        ? AppColors.successLight
                        : AppColors.warnLight,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    badge.earned
                        ? 'Obtenu'
                        : '${badge.xpRequired} XP requis',
                    style: GoogleFonts.nunito(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: badge.earned
                          ? AppColors.success
                          : AppColors.warn,
                    ),
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
                size: 56, color: AppColors.danger),
            const SizedBox(height: 16),
            Text(message,
                textAlign: TextAlign.center,
                style: GoogleFonts.nunito(color: AppColors.muted)),
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
