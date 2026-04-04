import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../shared/widgets/trust_score_gauge.dart';
import '../../../auth/data/models/user_model.dart';
import '../../data/models/profile_stats_model.dart';
import '../providers/profile_provider.dart';

// ---------------------------------------------------------------------------
// Écran principal
// ---------------------------------------------------------------------------

/// Onglet Profil dans la navigation principale.
class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final userAsync = ref.watch(profileProvider);
    final statsAsync = ref.watch(profileStatsProvider);

    return Scaffold(
      backgroundColor: AppTheme.bgPage,
      body: userAsync.when(
        loading: () => const _ProfileSkeleton(),
        error: (e, _) => _ProfileError(message: e.toString()),
        data: (user) {
          if (user == null) {
            return const _ProfileError(message: 'Utilisateur introuvable.');
          }
          return RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: () => ref.read(profileProvider.notifier).refresh(),
            child: CustomScrollView(
              slivers: [
                _ProfileHeader(
                  user: user,
                  statsAsync: statsAsync,
                ),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(14, 14, 14, 24),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      // Trust score card
                      statsAsync.when(
                        loading: () => const _StatsSkeleton(),
                        error: (_, __) => const SizedBox.shrink(),
                        data: (stats) => _TrustCard(stats: stats),
                      ),
                      const SizedBox(height: 10),
                      // KYC row
                      _KycRow(user: user),
                      const SizedBox(height: 10),
                      // Upgrade prompt (only if kycLevel < 2)
                      if (user.kycLevel < 2) ...[
                        _UpgradePrompt(kycLevel: user.kycLevel),
                        const SizedBox(height: 10),
                      ],
                      // Member since row
                      _MemberSinceRow(user: user),
                      const SizedBox(height: 10),
                      // Navigation rows
                      _NavRow(
                        icon: '⚙️',
                        label: 'Paramètres',
                        onTap: () => context.push('/profile/settings'),
                      ),
                      const SizedBox(height: 6),
                      _NavRow(
                        icon: '🤝',
                        label: 'Parrainage',
                        onTap: () => context.push('/profile/referral'),
                      ),
                    ]),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Profile header (navy → sky3 gradient)
// ---------------------------------------------------------------------------

class _ProfileHeader extends StatelessWidget {
  const _ProfileHeader({
    required this.user,
    required this.statsAsync,
  });

  final UserModel user;
  final AsyncValue<ProfileStatsModel> statsAsync;

  String _initials(String text) {
    final parts = text.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    if (text.length >= 2) return text.substring(0, 2).toUpperCase();
    return text.toUpperCase();
  }

  String _rank(int trustScore) {
    if (trustScore >= 80) return '🥇 Gold';
    if (trustScore >= 50) return '🥈 Silver';
    return '🥉 Bronze';
  }

  @override
  Widget build(BuildContext context) {
    final initials = _initials(user.name ?? user.phone ?? user.email ?? '?');
    final topPadding = MediaQuery.of(context).padding.top;

    return SliverToBoxAdapter(
      child: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppColors.navy, AppColors.sky3],
          ),
        ),
        child: Padding(
          padding: EdgeInsets.fromLTRB(16, topPadding + 12, 16, 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Top row: gear button
              Align(
                alignment: Alignment.topRight,
                child: GestureDetector(
                  onTap: () => context.push('/profile/settings'),
                  child: Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      color: Colors.white.withAlpha(38),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(
                      Icons.settings_outlined,
                      color: Colors.white,
                      size: 16,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              // Avatar + name
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Avatar with gradient border
                  Container(
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: AppColors.skyGradient,
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(2),
                      child: Container(
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          color: AppColors.navy,
                        ),
                        child: Center(
                          child: Text(
                            initials,
                            style: GoogleFonts.nunito(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user.name ?? 'Utilisateur',
                          style: GoogleFonts.nunito(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 2),
                        statsAsync.when(
                          loading: () => const SizedBox.shrink(),
                          error: (_, __) => const SizedBox.shrink(),
                          data: (stats) {
                            final cityLabel = stats.city != null && stats.city!.isNotEmpty
                                ? '📍 ${stats.city}, Côte d\'Ivoire'
                                : '📍 Côte d\'Ivoire';
                            return Text(
                              cityLabel,
                              style: GoogleFonts.nunito(
                                fontSize: 11,
                                color: Colors.white.withAlpha(191),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              // Stats row
              statsAsync.when(
                loading: () => const _StatsRowSkeleton(),
                error: (_, __) => const SizedBox.shrink(),
                data: (stats) => _StatsRow(
                  totalViews: stats.totalViews,
                  totalEarned: stats.totalEarned,
                  rank: _rank(stats.trustScore),
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
// Stats row in header
// ---------------------------------------------------------------------------

class _StatsRow extends StatelessWidget {
  const _StatsRow({
    required this.totalViews,
    required this.totalEarned,
    required this.rank,
  });

  final int totalViews;
  final int totalEarned;
  final String rank;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white.withAlpha(31),
        borderRadius: BorderRadius.circular(12),
      ),
      child: IntrinsicHeight(
        child: Row(
          children: [
            _StatCell(
              label: 'Vues totales',
              value: Formatters.compact(totalViews),
            ),
            _VerticalDivider(),
            _StatCell(
              label: 'Gains totaux',
              value: '${Formatters.compact(totalEarned)} F',
            ),
            _VerticalDivider(),
            _StatCell(
              label: 'Rang',
              value: rank,
            ),
          ],
        ),
      ),
    );
  }
}

class _StatCell extends StatelessWidget {
  const _StatCell({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 4),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: GoogleFonts.nunito(
                fontSize: 8,
                color: Colors.white.withAlpha(191),
                fontWeight: FontWeight.w500,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 3),
            Text(
              value,
              style: GoogleFonts.nunito(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _VerticalDivider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      width: 1,
      color: Colors.white.withAlpha(50),
    );
  }
}

class _StatsRowSkeleton extends StatelessWidget {
  const _StatsRowSkeleton();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 52,
      decoration: BoxDecoration(
        color: Colors.white.withAlpha(20),
        borderRadius: BorderRadius.circular(12),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Trust card
// ---------------------------------------------------------------------------

class _TrustCard extends StatelessWidget {
  const _TrustCard({required this.stats});

  final ProfileStatsModel stats;

  String _kycBadgeLabel(int kycLevel) {
    return switch (kycLevel) {
      0 => 'Non vérifié',
      1 => 'Niveau 1',
      2 => 'Niveau 2',
      3 => 'Niveau 3',
      _ => 'Inconnu',
    };
  }

  String _trustDescription(int score) {
    if (score >= 80) return 'Excellent profil de confiance. Continuez comme ça !';
    if (score >= 50) return 'Bon score. Améliorez votre KYC pour progresser.';
    return 'Score faible. Complétez votre profil pour l\'améliorer.';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
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
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          TrustScoreGauge(score: stats.trustScore, size: 68, strokeWidth: 7),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Score de confiance',
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  _trustDescription(stats.trustScore),
                  style: GoogleFonts.nunito(
                    fontSize: 11,
                    color: AppColors.muted,
                  ),
                ),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.warnLight,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    _kycBadgeLabel(stats.kycLevel),
                    style: GoogleFonts.nunito(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: AppColors.warn,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// KYC row
// ---------------------------------------------------------------------------

class _KycRow extends StatelessWidget {
  const _KycRow({required this.user});

  final UserModel user;

  String _kycLabel(int level) => switch (level) {
        0 => 'Non vérifié',
        1 => 'KYC Niveau 1',
        2 => 'KYC Niveau 2',
        3 => 'KYC Niveau 3',
        _ => 'Inconnu',
      };

  String _kycLimit(int level) => switch (level) {
        0 => 'Aucun retrait autorisé',
        1 => 'Limite retrait 10k/mois',
        2 => 'Limite retrait 50k/mois',
        3 => 'Retrait illimité',
        _ => '',
      };

  @override
  Widget build(BuildContext context) {
    final level = user.kycLevel.clamp(0, 3);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Text('🪪', style: TextStyle(fontSize: 20)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Vérification d\'identité',
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy,
                  ),
                ),
                Text(
                  '${_kycLabel(level)} · ${_kycLimit(level)}',
                  style: GoogleFonts.nunito(
                    fontSize: 10,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: AppColors.warnLight,
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              'Niveau $level',
              style: GoogleFonts.nunito(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: AppColors.warn,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Upgrade prompt
// ---------------------------------------------------------------------------

class _UpgradePrompt extends StatelessWidget {
  const _UpgradePrompt({required this.kycLevel});

  final int kycLevel;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.skyPale, AppColors.white],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Text('⬆️', style: TextStyle(fontSize: 20)),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Passer au Niveau ${kycLevel + 1}',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy,
                  ),
                ),
                Text(
                  'Débloquez 200k/mois · Priorité pub',
                  style: GoogleFonts.nunito(
                    fontSize: 10,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: () => context.push('/kyc'),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: AppColors.sky,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'Upgrade',
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
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
// Member since row
// ---------------------------------------------------------------------------

class _MemberSinceRow extends StatelessWidget {
  const _MemberSinceRow({required this.user});

  final UserModel user;

  String _formatMemberSince(String? isoDate) {
    if (isoDate == null) return '—';
    try {
      final dt = DateTime.parse(isoDate);
      return DateFormat('MMMM yyyy', 'fr_FR').format(dt);
    } catch (_) {
      return '—';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Text(
            'Membre depuis',
            style: GoogleFonts.nunito(
              fontSize: 13,
              color: AppColors.muted,
            ),
          ),
          const Spacer(),
          Text(
            _formatMemberSince(user.phoneVerifiedAt),
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppColors.navy,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Generic nav row
// ---------------------------------------------------------------------------

class _NavRow extends StatelessWidget {
  const _NavRow({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final String icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: AppColors.white,
          border: Border.all(color: AppColors.border),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          children: [
            Text(icon, style: const TextStyle(fontSize: 18)),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                label,
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppColors.navy,
                ),
              ),
            ),
            const Icon(
              Icons.chevron_right,
              color: AppColors.muted,
              size: 18,
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Skeleton de chargement
// ---------------------------------------------------------------------------

class _ProfileSkeleton extends StatelessWidget {
  const _ProfileSkeleton();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFE0E0E0),
      highlightColor: const Color(0xFFF5F5F5),
      child: ListView(
        children: [
          Container(height: 200, color: Colors.white),
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: Column(
              children: List.generate(
                4,
                (i) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Container(
                    height: 72,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatsSkeleton extends StatelessWidget {
  const _StatsSkeleton();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFE0E0E0),
      highlightColor: const Color(0xFFF5F5F5),
      child: Container(
        height: 80,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Écran d'erreur
// ---------------------------------------------------------------------------

class _ProfileError extends StatelessWidget {
  const _ProfileError({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, color: AppColors.danger, size: 56),
            const SizedBox(height: 16),
            Text(
              'Une erreur est survenue',
              style: GoogleFonts.nunito(
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(color: AppColors.muted),
            ),
          ],
        ),
      ),
    );
  }
}
