import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/services/device_service.dart';
import '../../data/models/campaign_model.dart';
import '../../data/repositories/feed_repository.dart';
import '../../../wallet/presentation/providers/wallet_provider.dart';

// ---------------------------------------------------------------------------
// Feed notifier
// ---------------------------------------------------------------------------

class FeedNotifier extends AsyncNotifier<List<CampaignModel>> {
  @override
  Future<List<CampaignModel>> build() async {
    return ref.read(feedRepositoryProvider).getFeed();
  }

  /// Forces a network refresh, bypassing the Hive cache.
  Future<void> refresh() async {
    ref.read(feedRepositoryProvider).invalidateCache();
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(feedRepositoryProvider).getFeed(),
    );
  }

  /// Removes a viewed campaign from the local list (optimistic update).
  void markViewed(int campaignId) {
    final current = state.valueOrNull;
    if (current == null) return;
    state = AsyncData(
      current.where((c) => c.id != campaignId).toList(),
    );
  }
}

final feedProvider =
    AsyncNotifierProvider<FeedNotifier, List<CampaignModel>>(
        FeedNotifier.new);

// ---------------------------------------------------------------------------
// Ad view state — tracks a single in-progress ad view
// ---------------------------------------------------------------------------

class AdViewState {
  const AdViewState({
    this.adViewId,
    this.startedAt,
    this.isViewing = false,
    this.isCompleted = false,
  });

  final int? adViewId;
  final DateTime? startedAt;
  final bool isViewing;
  final bool isCompleted;

  AdViewState copyWith({
    int? adViewId,
    DateTime? startedAt,
    bool? isViewing,
    bool? isCompleted,
  }) {
    return AdViewState(
      adViewId: adViewId ?? this.adViewId,
      startedAt: startedAt ?? this.startedAt,
      isViewing: isViewing ?? this.isViewing,
      isCompleted: isCompleted ?? this.isCompleted,
    );
  }

  static const initial = AdViewState();
}

class AdViewNotifier extends StateNotifier<AdViewState> {
  AdViewNotifier(this._ref) : super(AdViewState.initial);

  final Ref _ref;

  Future<void> startView(int campaignId) async {
    if (state.isViewing) return;

    try {
      final fingerprint =
          await _ref.read(deviceFingerprintProvider.future);
      final platform =
          await _ref.read(deviceServiceProvider).getPlatform();

      final adViewId = await _ref
          .read(feedRepositoryProvider)
          .startView(campaignId,
              fingerprint: fingerprint, platform: platform);

      state = AdViewState(
        adViewId: adViewId,
        startedAt: DateTime.now(),
        isViewing: true,
        isCompleted: false,
      );
    } catch (_) {
      // Silently degrade — do not block the player on network error.
    }
  }

  Future<ViewResult?> completeView(
    int campaignId,
    int watchDurationSeconds,
  ) async {
    final adViewId = state.adViewId;
    if (adViewId == null || state.isCompleted) return null;

    try {
      final result = await _ref
          .read(feedRepositoryProvider)
          .completeView(campaignId, adViewId, watchDurationSeconds);

      state = state.copyWith(isCompleted: true, isViewing: false);
      return result;
    } catch (_) {
      return null;
    }
  }

  void reset() => state = AdViewState.initial;
}

final adViewProvider =
    StateNotifierProvider<AdViewNotifier, AdViewState>((ref) {
  return AdViewNotifier(ref);
});

// ---------------------------------------------------------------------------
// Feed stats — today's earnings and ad view count derived from wallet data
// ---------------------------------------------------------------------------

/// Daily stats displayed in the feed's wallet card.
class FeedDailyStats {
  const FeedDailyStats({
    required this.todayEarned,
    required this.adsToday,
    required this.adsTotal,
  });

  /// FCFA credited today from ad views.
  final int todayEarned;

  /// Number of ads watched today.
  final int adsToday;

  /// Maximum ads allowed per day (from AppConfig).
  final int adsTotal;
}

/// Derives today's earned amount and ad view count from the wallet's recent
/// transactions. Filters credit transactions whose [createdAt] date matches
/// today's local date.
final feedDailyStatsProvider = Provider<FeedDailyStats>((ref) {
  final walletAsync = ref.watch(walletProvider);

  return walletAsync.when(
    data: (wallet) {
      final today = DateTime.now();
      final todayTransactions = wallet.recentTransactions.where((tx) {
        if (!tx.isCredit) return false;
        try {
          final txDate = DateTime.parse(tx.createdAt).toLocal();
          return txDate.year == today.year &&
              txDate.month == today.month &&
              txDate.day == today.day;
        } catch (_) {
          return false;
        }
      }).toList();

      final todayEarned = todayTransactions.fold<int>(
        0,
        (sum, tx) => sum + tx.amount,
      );

      return FeedDailyStats(
        todayEarned: todayEarned,
        adsToday: todayTransactions.length,
        adsTotal: AppConfig.maxViewsPerDay,
      );
    },
    loading: () => const FeedDailyStats(
      todayEarned: 0,
      adsToday: 0,
      adsTotal: AppConfig.maxViewsPerDay,
    ),
    error: (_, __) => const FeedDailyStats(
      todayEarned: 0,
      adsToday: 0,
      adsTotal: AppConfig.maxViewsPerDay,
    ),
  );
});
