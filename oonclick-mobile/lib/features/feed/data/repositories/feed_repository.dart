import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../../../../core/services/hive_service.dart';
import '../models/campaign_model.dart';

/// Result of a completed ad view.
class ViewResult {
  const ViewResult({
    required this.credited,
    required this.amount,
    required this.newBalance,
    this.reason,
  });

  final bool credited;

  /// FCFA credited to the wallet.
  final int amount;

  /// Wallet balance after this transaction.
  final int newBalance;

  /// Reason if [credited] is false (e.g. "already_viewed", "fraud_detected").
  final String? reason;

  factory ViewResult.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    return ViewResult(
      credited: data['credited'] as bool? ?? false,
      amount: (data['amount'] as num?)?.toInt() ?? 0,
      newBalance: (data['new_balance'] as num?)?.toInt() ?? 0,
      reason: data['reason'] as String?,
    );
  }
}

// ---------------------------------------------------------------------------
// Feed cache keys & TTL
// ---------------------------------------------------------------------------

const _feedCacheKey = 'feed_campaigns';
const _feedCachedAtKey = 'feed_cached_at';
const _feedCacheTtlMinutes = 5;

/// Repository for the advertising feed.
///
/// Caches the feed in Hive with a 5-minute TTL so the app stays usable
/// without network access.
class FeedRepository {
  FeedRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Get feed
  // ---------------------------------------------------------------------------

  /// Returns the current feed, using the Hive cache when it is still fresh.
  ///
  /// GET /feed
  Future<List<CampaignModel>> getFeed() async {
    // Try cache first.
    final cached = _readCache();
    if (cached != null) return cached;

    try {
      final response =
          await _api.get<Map<String, dynamic>>('/feed');
      final data = response.data as Map<String, dynamic>;
      final rawList = data['data'] as List<dynamic>? ?? [];

      final campaigns = rawList
          .whereType<Map<String, dynamic>>()
          .map(CampaignModel.fromJson)
          .toList();

      _writeCache(campaigns);
      return campaigns;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Start view
  // ---------------------------------------------------------------------------

  /// Registers the start of an ad view and returns the server-assigned
  /// `ad_view_id` used to correlate the completion call.
  ///
  /// POST /ads/{id}/start
  Future<int> startView(
    int campaignId, {
    String? fingerprint,
    String? platform,
  }) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/ads/$campaignId/start',
        data: {
          'fingerprint': fingerprint,
          'platform': platform,
        },
      );
      final data = response.data as Map<String, dynamic>;
      final inner = data['data'] as Map<String, dynamic>? ?? data;
      return (inner['ad_view_id'] as num).toInt();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Complete view
  // ---------------------------------------------------------------------------

  /// Notifies the backend that the user has finished watching an ad.
  ///
  /// POST /ads/{id}/complete
  Future<ViewResult> completeView(
    int campaignId,
    int adViewId,
    int watchDurationSeconds,
  ) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/ads/$campaignId/complete',
        data: {
          'ad_view_id': adViewId,
          'watch_duration_seconds': watchDurationSeconds,
        },
      );
      return ViewResult.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Cache helpers
  // ---------------------------------------------------------------------------

  List<CampaignModel>? _readCache() {
    final box = HiveService.feedCache;
    final cachedAtRaw = box.get(_feedCachedAtKey) as int?;
    if (cachedAtRaw == null) return null;

    final cachedAt =
        DateTime.fromMillisecondsSinceEpoch(cachedAtRaw);
    if (DateTime.now().difference(cachedAt).inMinutes >=
        _feedCacheTtlMinutes) {
      return null;
    }

    final raw = box.get(_feedCacheKey) as String?;
    if (raw == null) return null;

    try {
      final list = jsonDecode(raw) as List<dynamic>;
      return list
          .whereType<Map<String, dynamic>>()
          .map(CampaignModel.fromJson)
          .toList();
    } catch (_) {
      return null;
    }
  }

  void _writeCache(List<CampaignModel> campaigns) {
    final box = HiveService.feedCache;
    final json =
        jsonEncode(campaigns.map((c) => c.toJson()).toList());
    box.put(_feedCacheKey, json);
    box.put(
      _feedCachedAtKey,
      DateTime.now().millisecondsSinceEpoch,
    );
  }

  void invalidateCache() {
    final box = HiveService.feedCache;
    box.delete(_feedCacheKey);
    box.delete(_feedCachedAtKey);
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final feedRepositoryProvider = Provider<FeedRepository>((ref) {
  return FeedRepository(ref.read(apiClientProvider));
});
