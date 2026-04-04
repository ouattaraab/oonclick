import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/models/gamification_model.dart';
import '../providers/gamification_provider.dart';

/// Écran de classement — podium Top 3 + liste.
class LeaderboardScreen extends ConsumerWidget {
  const LeaderboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final leaderboardAsync = ref.watch(leaderboardProvider);
    final currentUser = ref.watch(currentUserProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _LeaderboardTopBar(),
          Expanded(
            child: leaderboardAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _ErrorView(
                message: err.toString(),
                onRetry: () => ref.invalidate(leaderboardProvider),
              ),
              data: (entries) {
                if (entries.isEmpty) {
                  return Center(
                    child: Text(
                      'Classement vide pour l\'instant.',
                      style: GoogleFonts.nunito(color: AppColors.muted),
                    ),
                  );
                }

                final top3 = entries.take(3).toList();
                final rest = entries.length > 3 ? entries.sublist(3) : [];

                return RefreshIndicator(
                  color: AppColors.sky,
                  onRefresh: () async =>
                      ref.invalidate(leaderboardProvider),
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 40),
                    children: [
                      // Podium
                      _Podium(top3: top3, currentUserId: currentUser?.id),
                      const SizedBox(height: 20),

                      if (rest.isNotEmpty) ...[
                        Text(
                          'Suite du classement',
                          style: GoogleFonts.nunito(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: AppColors.navy,
                          ),
                        ),
                        const SizedBox(height: 10),
                        ...rest.map((entry) => _LeaderboardTile(
                              entry: entry as LeaderboardEntry,
                              isCurrentUser:
                                  entry.userId == currentUser?.id,
                            )),
                      ],
                    ],
                  ),
                );
              },
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

class _LeaderboardTopBar extends StatelessWidget {
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
            'Classement',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          const Text('🏆', style: TextStyle(fontSize: 22)),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Podium (Top 3)
// ---------------------------------------------------------------------------

class _Podium extends StatelessWidget {
  const _Podium({required this.top3, this.currentUserId});

  final List<LeaderboardEntry> top3;
  final int? currentUserId;

  @override
  Widget build(BuildContext context) {
    final first = top3.isNotEmpty ? top3[0] : null;
    final second = top3.length > 1 ? top3[1] : null;
    final third = top3.length > 2 ? top3[2] : null;

    return Container(
      margin: const EdgeInsets.fromLTRB(0, 20, 0, 0),
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.navy, AppColors.sky3],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        children: [
          Text(
            'Top 3',
            style: GoogleFonts.nunito(
              fontSize: 13,
              color: Colors.white.withAlpha(180),
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 20),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // 2nd place
              if (second != null)
                _PodiumColumn(
                  entry: second,
                  medal: '🥈',
                  height: 80,
                  isCurrentUser: second.userId == currentUserId,
                )
              else
                const Expanded(child: SizedBox()),
              const SizedBox(width: 8),

              // 1st place
              if (first != null)
                _PodiumColumn(
                  entry: first,
                  medal: '🥇',
                  height: 110,
                  isCurrentUser: first.userId == currentUserId,
                )
              else
                const Expanded(child: SizedBox()),
              const SizedBox(width: 8),

              // 3rd place
              if (third != null)
                _PodiumColumn(
                  entry: third,
                  medal: '🥉',
                  height: 60,
                  isCurrentUser: third.userId == currentUserId,
                )
              else
                const Expanded(child: SizedBox()),
            ],
          ),
        ],
      ),
    );
  }
}

class _PodiumColumn extends StatelessWidget {
  const _PodiumColumn({
    required this.entry,
    required this.medal,
    required this.height,
    required this.isCurrentUser,
  });

  final LeaderboardEntry entry;
  final String medal;
  final double height;
  final bool isCurrentUser;

  String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return name.isNotEmpty ? name[0].toUpperCase() : '?';
  }

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          // Medal
          Text(medal, style: const TextStyle(fontSize: 22)),
          const SizedBox(height: 6),

          // Avatar
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isCurrentUser ? AppColors.sky : Colors.white.withAlpha(30),
              border: Border.all(
                color: isCurrentUser ? AppColors.sky : Colors.white.withAlpha(80),
                width: 2,
              ),
            ),
            child: Center(
              child: Text(
                _initials(entry.name),
                style: GoogleFonts.nunito(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ),
          ),
          const SizedBox(height: 6),

          // Name
          Text(
            entry.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
            style: GoogleFonts.nunito(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),

          // XP
          Text(
            '${entry.xp} XP',
            style: GoogleFonts.nunito(
              fontSize: 10,
              color: Colors.white.withAlpha(180),
            ),
          ),
          const SizedBox(height: 4),

          // Podium bar
          Container(
            width: double.infinity,
            height: height,
            decoration: BoxDecoration(
              color: Colors.white.withAlpha(isCurrentUser ? 50 : 30),
              borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(8)),
            ),
            child: Center(
              child: Text(
                '#${entry.rank}',
                style: GoogleFonts.nunito(
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
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
// Leaderboard tile (rank 4+)
// ---------------------------------------------------------------------------

class _LeaderboardTile extends StatelessWidget {
  const _LeaderboardTile({
    required this.entry,
    required this.isCurrentUser,
  });

  final LeaderboardEntry entry;
  final bool isCurrentUser;

  String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return name.isNotEmpty ? name[0].toUpperCase() : '?';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: isCurrentUser ? AppColors.skyPale : AppColors.white,
        border: Border.all(
          color: isCurrentUser ? AppColors.sky.withAlpha(80) : AppColors.border,
          width: isCurrentUser ? 1.5 : 1,
        ),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          // Rank
          SizedBox(
            width: 32,
            child: Text(
              '#${entry.rank}',
              style: GoogleFonts.nunito(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: isCurrentUser ? AppColors.sky : AppColors.muted,
              ),
            ),
          ),

          // Avatar
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: isCurrentUser ? AppColors.skyGradient : null,
              color: isCurrentUser ? null : AppColors.bg,
            ),
            child: Center(
              child: Text(
                _initials(entry.name),
                style: GoogleFonts.nunito(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: isCurrentUser ? Colors.white : AppColors.navy,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),

          // Name + level
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${entry.name}${isCurrentUser ? ' (Moi)' : ''}',
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy,
                  ),
                ),
                Text(
                  'Niveau ${entry.level}',
                  style: GoogleFonts.nunito(
                    fontSize: 11,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),

          // XP
          Text(
            '${entry.xp} XP',
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: isCurrentUser ? AppColors.sky : AppColors.navy,
            ),
          ),
        ],
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
