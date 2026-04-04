import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shimmer/shimmer.dart';

import '../../../../core/services/offline_sync_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../notifications/presentation/widgets/notification_badge.dart';
import '../providers/feed_provider.dart';
import '../widgets/campaign_card.dart';

// ---------------------------------------------------------------------------
// Category filter pills
// ---------------------------------------------------------------------------

/// Maps backend format slugs to localised display labels.
const _formatLabels = <String, String>{
  'video': 'Vidéo',
  'flash': 'Flash',
  'quiz': 'Quiz',
  'scratch': 'Grattage',
  'photo': 'Photo',
};

/// Builds the category pill list dynamically from the loaded campaigns.
/// Always starts with "Tout" and adds only the formats present in [campaigns].
List<String> _buildCategories(List<dynamic> campaigns) {
  final seen = <String>{};
  final labels = <String>['Tout'];
  for (final c in campaigns) {
    final slug = (c.format as String).toLowerCase();
    if (seen.add(slug)) {
      labels.add(_formatLabels[slug] ?? slug);
    }
  }
  return labels;
}

// ---------------------------------------------------------------------------
// Feed Screen
// ---------------------------------------------------------------------------

/// Main feed screen — home tab of the oon.click app.
class FeedScreen extends ConsumerStatefulWidget {
  const FeedScreen({super.key});

  @override
  ConsumerState<FeedScreen> createState() => _FeedScreenState();
}

class _FeedScreenState extends ConsumerState<FeedScreen> {
  String _activeCategory = 'Tout';

  @override
  Widget build(BuildContext context) {
    final feedAsync = ref.watch(feedProvider);
    final authState = ref.watch(authStateProvider);
    final dailyStats = ref.watch(feedDailyStatsProvider);

    final offlineService = ref.read(offlineSyncProvider);
    final offlineCampaigns = offlineService.getOfflineCampaigns();
    final pendingSync = offlineService.pendingCount;

    // Build the category list from whichever campaigns are currently loaded.
    final categories = _buildCategories(feedAsync.valueOrNull ?? []);

    // If the active category is no longer present (e.g. after a refresh that
    // returns fewer formats), reset to 'Tout'.
    if (!categories.contains(_activeCategory)) {
      _activeCategory = 'Tout';
    }

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        child: Column(
          children: [
            // ---- Top bar ----
            _FeedTopBar(
              balance: authState.walletBalance,
              trustScore: authState.user?.trustScore ?? 0,
            ),

            // ---- Offline mode banner ----
            if (offlineCampaigns.isNotEmpty || pendingSync > 0)
              _OfflineBanner(
                offlineCount: offlineCampaigns.length,
                pendingSync:  pendingSync,
                onSync: () async {
                  await offlineService.syncCompletions();
                  // Rafraichir le feed après sync
                  ref.read(feedProvider.notifier).refresh();
                },
              ),

            // ---- Wallet card ----
            _WalletCard(
              balance: authState.walletBalance,
              todayEarned: dailyStats.todayEarned,
              adsToday: dailyStats.adsToday,
              adsTotal: dailyStats.adsTotal,
            ),

            const SizedBox(height: 8),

            // ---- Category pills (built from live campaign formats) ----
            SizedBox(
              height: 40,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: categories.length,
                itemBuilder: (ctx, i) {
                  final cat = categories[i];
                  final isActive = cat == _activeCategory;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: GestureDetector(
                      onTap: () => setState(() => _activeCategory = cat),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          gradient: isActive ? AppColors.skyGradient : null,
                          color: isActive ? null : Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color:
                                isActive ? Colors.transparent : AppColors.border,
                          ),
                        ),
                        child: Text(
                          cat,
                          style: GoogleFonts.nunito(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color:
                                isActive ? Colors.white : AppColors.muted,
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),

            const SizedBox(height: 12),

            // ---- Section header ----
            feedAsync.when(
              loading: () => const SizedBox.shrink(),
              error: (e, st) => const SizedBox.shrink(),
              data: (campaigns) => Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  children: [
                    Text(
                      'Publicités disponibles',
                      style: GoogleFonts.nunito(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.navy,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.skyPale,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '${campaigns.length}',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: AppColors.sky,
                        ),
                      ),
                    ),
                    const Spacer(),
                    GestureDetector(
                      onTap: () => context.push('/ads/history'),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.history_rounded,
                              size: 16, color: AppColors.sky2),
                          const SizedBox(width: 4),
                          Text(
                            'Historique',
                            style: GoogleFonts.nunito(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: AppColors.sky2,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 10),

            // ---- Content ----
            Expanded(
              child: feedAsync.when(
                loading: () => const _FeedShimmer(),
                error: (err, _) {
                  // Si des campagnes hors-ligne sont disponibles, les afficher
                  if (offlineCampaigns.isNotEmpty) {
                    return _OfflineFeedList(
                      campaigns: offlineCampaigns,
                      onRetry: () =>
                          ref.read(feedProvider.notifier).refresh(),
                    );
                  }
                  return _FeedError(
                    message: err.toString(),
                    onRetry: () =>
                        ref.read(feedProvider.notifier).refresh(),
                  );
                },
                data: (campaigns) {
                  // Apply category filter.
                  // Reverse-lookup the slug for the active display label so
                  // the filter works regardless of how _formatLabels is extended.
                  final activeSlug = _activeCategory == 'Tout'
                      ? null
                      : _formatLabels.entries
                          .where((e) => e.value == _activeCategory)
                          .map((e) => e.key)
                          .firstOrNull;
                  final filtered = activeSlug == null
                      ? campaigns
                      : campaigns
                          .where((c) =>
                              c.format.toLowerCase() == activeSlug)
                          .toList();

                  if (filtered.isEmpty) {
                    return _FeedEmpty(
                      onRefresh: () =>
                          ref.read(feedProvider.notifier).refresh(),
                    );
                  }

                  return RefreshIndicator(
                    color: AppColors.sky,
                    onRefresh: () =>
                        ref.read(feedProvider.notifier).refresh(),
                    child: ListView.builder(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                      itemCount: filtered.length,
                      itemBuilder: (_, index) {
                        final campaign = filtered[index];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: CampaignCard(
                            campaign: campaign,
                            onTap: () =>
                                context.push('/ad/${campaign.id}'),
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _FeedTopBar extends ConsumerWidget {
  const _FeedTopBar({required this.balance, required this.trustScore});

  final int balance;
  final int trustScore;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(
          bottom: BorderSide(color: AppColors.border, width: 1),
        ),
      ),
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
      child: Row(
        children: [
          // Logo
          RichText(
            text: TextSpan(
              children: [
                TextSpan(
                  text: 'oon',
                  style: GoogleFonts.nunito(
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                    color: AppColors.sky,
                  ),
                ),
                TextSpan(
                  text: '.click',
                  style: GoogleFonts.nunito(
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                    color: AppColors.navy,
                  ),
                ),
              ],
            ),
          ),

          const Spacer(),

          // Trust chip
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: AppColors.skyPale,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppColors.border),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.shield_rounded,
                    size: 13, color: AppColors.sky),
                const SizedBox(width: 4),
                Text(
                  '$trustScore/100',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),

          // Notifications
          NotificationBadge(
            child: GestureDetector(
              onTap: () => GoRouter.of(context).go('/notifications'),
              child: Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: AppColors.skyPale,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.notifications_outlined,
                  size: 18,
                  color: AppColors.navy,
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
// Wallet summary card
// ---------------------------------------------------------------------------

class _WalletCard extends StatelessWidget {
  const _WalletCard({
    required this.balance,
    required this.todayEarned,
    required this.adsToday,
    required this.adsTotal,
  });

  final int balance;
  final int todayEarned;
  final int adsToday;
  final int adsTotal;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 10, 16, 0),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: AppColors.navyGradientDiagonal,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                'Mon solde',
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  color: Colors.white.withAlpha(200),
                  fontWeight: FontWeight.w600,
                ),
              ),
              const Spacer(),
              // Withdraw button — glassmorphism style
              GestureDetector(
                onTap: () => GoRouter.of(context).go('/wallet'),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white.withAlpha(45),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                        color: Colors.white.withAlpha(60)),
                  ),
                  child: Text(
                    'Retirer',
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
          const SizedBox(height: 6),
          Text(
            Formatters.currency(balance),
            style: GoogleFonts.nunito(
              fontSize: 28,
              fontWeight: FontWeight.w900,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 12),
          // Stats row
          Row(
            children: [
              _WalletStat(
                label: 'Aujourd\'hui',
                value: '+${Formatters.currency(todayEarned)}',
              ),
              const SizedBox(width: 12),
              _WalletStat(
                label: 'Pubs vues',
                value: '$adsToday/$adsTotal',
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _WalletStat extends StatelessWidget {
  const _WalletStat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withAlpha(30),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: GoogleFonts.nunito(
              fontSize: 10,
              color: Colors.white.withAlpha(180),
              fontWeight: FontWeight.w500,
            ),
          ),
          Text(
            value,
            style: GoogleFonts.nunito(
              fontSize: 13,
              color: Colors.white,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Offline mode banner
// ---------------------------------------------------------------------------

class _OfflineBanner extends StatelessWidget {
  const _OfflineBanner({
    required this.offlineCount,
    required this.pendingSync,
    required this.onSync,
  });

  final int offlineCount;
  final int pendingSync;
  final VoidCallback onSync;

  @override
  Widget build(BuildContext context) {
    final hasPending = pendingSync > 0;
    final bgColor = hasPending
        ? const Color(0xFFFFF8E1) // ambre clair pour indiquer action requise
        : const Color(0xFFE8F5E9); // vert très clair pour disponible

    final iconColor = hasPending
        ? const Color(0xFFF59E0B)
        : AppColors.success;

    String label;
    if (hasPending && offlineCount > 0) {
      label = '$offlineCount pub${offlineCount > 1 ? 's' : ''} disponible${offlineCount > 1 ? 's' : ''} hors-ligne  •  $pendingSync en attente de sync';
    } else if (hasPending) {
      label = '$pendingSync visionnage${pendingSync > 1 ? 's' : ''} en attente de synchronisation';
    } else {
      label = '$offlineCount pub${offlineCount > 1 ? 's' : ''} disponible${offlineCount > 1 ? 's' : ''} hors-ligne';
    }

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: iconColor.withAlpha(60)),
      ),
      child: Row(
        children: [
          Icon(
            hasPending
                ? Icons.sync_rounded
                : Icons.offline_bolt_rounded,
            size: 16,
            color: iconColor,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              label,
              style: GoogleFonts.nunito(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: AppColors.navy,
              ),
            ),
          ),
          if (hasPending)
            GestureDetector(
              onTap: onSync,
              child: Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: iconColor,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  'Sync',
                  style: GoogleFonts.nunito(
                    fontSize: 10,
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
// Offline feed list — affiche les campagnes pré-chargées
// ---------------------------------------------------------------------------

class _OfflineFeedList extends StatelessWidget {
  const _OfflineFeedList({
    required this.campaigns,
    required this.onRetry,
  });

  final List<Map<String, dynamic>> campaigns;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: Row(
            children: [
              const Icon(Icons.offline_bolt_rounded,
                  size: 16, color: AppColors.sky),
              const SizedBox(width: 6),
              Text(
                'Mode hors-ligne',
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppColors.navy,
                ),
              ),
              const Spacer(),
              GestureDetector(
                onTap: onRetry,
                child: Text(
                  'Actualiser',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: AppColors.sky,
                  ),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
            itemCount: campaigns.length,
            itemBuilder: (_, index) {
              final c = campaigns[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _OfflineCampaignTile(campaign: c),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _OfflineCampaignTile extends StatelessWidget {
  const _OfflineCampaignTile({required this.campaign});

  final Map<String, dynamic> campaign;

  @override
  Widget build(BuildContext context) {
    final title  = campaign['title'] as String? ?? 'Publicité';
    final format = campaign['format'] as String? ?? '';
    final amount = (campaign['amount'] as num?)?.toInt() ?? 0;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.skyPale,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.offline_bolt_rounded,
              color: AppColors.sky,
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                if (format.isNotEmpty)
                  Text(
                    format.toUpperCase(),
                    style: GoogleFonts.nunito(
                      fontSize: 10,
                      color: AppColors.muted,
                    ),
                  ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(
                horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: AppColors.success.withAlpha(25),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              '+$amount F',
              style: GoogleFonts.nunito(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppColors.success,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Shimmer skeleton
// ---------------------------------------------------------------------------

class _FeedShimmer extends StatelessWidget {
  const _FeedShimmer();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: AppColors.border,
      highlightColor: Colors.white,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
        itemCount: 4,
        itemBuilder: (ctx, idx) => Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Container(
            height: 80,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Error state
// ---------------------------------------------------------------------------

class _FeedError extends StatelessWidget {
  const _FeedError({required this.message, required this.onRetry});

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
            const Icon(Icons.wifi_off_rounded,
                size: 56, color: AppColors.muted),
            const SizedBox(height: 16),
            Text(
              'Impossible de charger le feed',
              style: GoogleFonts.nunito(
                fontWeight: FontWeight.w700,
                fontSize: 16,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                  color: AppColors.muted, fontSize: 13),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('Réessayer'),
              style: ElevatedButton.styleFrom(
                  minimumSize: const Size(160, 44)),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Empty state with countdown timer
// ---------------------------------------------------------------------------

class _FeedEmpty extends StatefulWidget {
  const _FeedEmpty({required this.onRefresh});

  final VoidCallback onRefresh;

  @override
  State<_FeedEmpty> createState() => _FeedEmptyState();
}

class _FeedEmptyState extends State<_FeedEmpty> {
  late int _seconds;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    // Countdown to next batch (arbitrary: 4h from now as example)
    _seconds = 4 * 3600;
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (_seconds > 0) {
        setState(() => _seconds--);
      } else {
        _timer?.cancel();
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  String get _countdownStr {
    final h = _seconds ~/ 3600;
    final m = (_seconds % 3600) ~/ 60;
    final s = _seconds % 60;
    return '${h.toString().padLeft(2, '0')}:${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Timer icon circle
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: AppColors.skyPale,
                shape: BoxShape.circle,
              ),
              child: const Center(
                child: Text('⏰', style: TextStyle(fontSize: 32)),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'Pas de pub disponible',
              style: GoogleFonts.nunito(
                fontSize: 17,
                fontWeight: FontWeight.w800,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Prochaine session dans :',
              style: GoogleFonts.nunito(
                fontSize: 13,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              _countdownStr,
              style: GoogleFonts.nunito(
                fontSize: 28,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: widget.onRefresh,
              style: ElevatedButton.styleFrom(
                  minimumSize: const Size(180, 44)),
              child: const Text('Voir mes gains'),
            ),
          ],
        ),
      ),
    );
  }
}
