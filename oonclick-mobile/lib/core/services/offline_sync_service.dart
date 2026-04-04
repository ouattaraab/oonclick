import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_client.dart';
import 'hive_service.dart';

// ---------------------------------------------------------------------------
// OfflineSyncService
// ---------------------------------------------------------------------------

/// Gère le pré-chargement des campagnes et la synchronisation des visionnages
/// effectués hors-ligne.
///
/// Utilise la box Hive `settings` pour stocker :
/// - `offline_campaigns`    : JSON des campagnes pré-chargées.
/// - `offline_preloaded_at` : horodatage ISO8601 du dernier pré-chargement.
/// - `pending_completions`  : JSON des visionnages en attente de sync.
class OfflineSyncService {
  OfflineSyncService(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Pré-chargement
  // ---------------------------------------------------------------------------

  /// Récupère les campagnes pré-chargées depuis le serveur et les stocke en local.
  ///
  /// Retourne le nombre de campagnes sauvegardées, ou 0 en cas d'erreur réseau.
  Future<int> preloadCampaigns() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/feed/preload');
      final data = response.data as Map<String, dynamic>;
      final campaigns = data['campaigns'] as List<dynamic>;

      final box = HiveService.settings;
      await box.put('offline_campaigns', jsonEncode(campaigns));
      await box.put(
          'offline_preloaded_at', DateTime.now().toIso8601String());

      return campaigns.length;
    } catch (_) {
      return 0;
    }
  }

  /// Retourne les campagnes pré-chargées depuis le cache local.
  ///
  /// Retourne une liste vide si aucun cache n'est disponible ou si le cache
  /// est expiré (déterminé par le champ `valid_until` de chaque campagne).
  List<Map<String, dynamic>> getOfflineCampaigns() {
    final box = HiveService.settings;
    final raw = box.get('offline_campaigns') as String?;
    if (raw == null || raw.isEmpty) return [];

    try {
      final list =
          (jsonDecode(raw) as List).cast<Map<String, dynamic>>();

      // Filtrer les campagnes dont la validité n'a pas expiré
      final now = DateTime.now();
      return list.where((c) {
        final validUntilStr = c['valid_until'] as String?;
        if (validUntilStr == null) return true;
        try {
          final validUntil = DateTime.parse(validUntilStr);
          return validUntil.isAfter(now);
        } catch (_) {
          return true;
        }
      }).toList();
    } catch (_) {
      return [];
    }
  }

  /// Indique si des campagnes hors-ligne valides sont disponibles.
  bool get hasOfflineCampaigns => getOfflineCampaigns().isNotEmpty;

  // ---------------------------------------------------------------------------
  // Completions en attente
  // ---------------------------------------------------------------------------

  /// Enregistre un visionnage complété hors-ligne pour synchronisation ultérieure.
  ///
  /// [campaignId]    : identifiant de la campagne regardée.
  /// [watchDuration] : durée effective de visionnage en secondes.
  Future<void> storeCompletion(int campaignId, int watchDuration) async {
    final box = HiveService.settings;
    final raw =
        box.get('pending_completions', defaultValue: '[]') as String;

    try {
      final list =
          (jsonDecode(raw) as List).cast<Map<String, dynamic>>();
      list.add({
        'campaign_id': campaignId,
        'watch_duration_seconds': watchDuration,
        'completed_at': DateTime.now().toIso8601String(),
      });
      await box.put('pending_completions', jsonEncode(list));
    } catch (_) {
      // En cas de corruption du cache, réinitialiser
      await box.put(
        'pending_completions',
        jsonEncode([
          {
            'campaign_id': campaignId,
            'watch_duration_seconds': watchDuration,
            'completed_at': DateTime.now().toIso8601String(),
          }
        ]),
      );
    }
  }

  /// Synchronise les visionnages en attente avec le serveur.
  ///
  /// En cas de succès, vide la liste locale. En cas d'erreur, conserve
  /// les entrées pour une nouvelle tentative ultérieure.
  ///
  /// Retourne le nombre d'entrées synchronisées, ou 0 en cas d'erreur.
  Future<int> syncCompletions() async {
    final box = HiveService.settings;
    final raw =
        box.get('pending_completions', defaultValue: '[]') as String;

    List<Map<String, dynamic>> list;
    try {
      list = (jsonDecode(raw) as List).cast<Map<String, dynamic>>();
    } catch (_) {
      list = [];
    }

    if (list.isEmpty) return 0;

    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/feed/sync',
        data: {'completions': list},
      );

      // Vider les completions synchronisées
      await box.put('pending_completions', '[]');

      final data = response.data as Map<String, dynamic>;
      return (data['synced_count'] as num?)?.toInt() ?? 0;
    } catch (_) {
      // Conserver les entrées pour la prochaine tentative
      return 0;
    }
  }

  /// Nombre de visionnages en attente de synchronisation.
  int get pendingCount {
    final raw =
        HiveService.settings.get('pending_completions', defaultValue: '[]')
            as String;
    try {
      return (jsonDecode(raw) as List).length;
    } catch (_) {
      return 0;
    }
  }

  /// Horodatage du dernier pré-chargement, ou null si jamais effectué.
  DateTime? get lastPreloadedAt {
    final raw = HiveService.settings.get('offline_preloaded_at') as String?;
    if (raw == null) return null;
    try {
      return DateTime.parse(raw);
    } catch (_) {
      return null;
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final offlineSyncProvider = Provider<OfflineSyncService>((ref) {
  return OfflineSyncService(ref.read(apiClientProvider));
});
