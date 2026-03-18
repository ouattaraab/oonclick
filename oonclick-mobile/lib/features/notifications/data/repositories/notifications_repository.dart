import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/notification_model.dart';

/// Repository gérant tous les endpoints de notifications.
///
/// Toutes les méthodes lèvent [ApiException] en cas d'erreur serveur ou réseau.
class NotificationsRepository {
  NotificationsRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Récupérer les notifications
  // ---------------------------------------------------------------------------

  /// GET /notifications?unread=1 (si [unreadOnly] est vrai)
  /// GET /notifications (sinon)
  ///
  /// Retourne la liste de notifications triée par date décroissante.
  Future<List<NotificationModel>> getNotifications({
    bool unreadOnly = false,
  }) async {
    try {
      final response = await _api.get<Map<String, dynamic>>(
        '/notifications',
        params: unreadOnly ? {'unread': '1'} : null,
      );
      final body = response.data as Map<String, dynamic>;
      // Supporte les formats `{ data: [...] }` et `[...]` directement.
      final List<dynamic> raw;
      if (body['data'] is List) {
        raw = body['data'] as List<dynamic>;
      } else {
        raw = body['notifications'] as List<dynamic>? ?? [];
      }

      return raw
          .whereType<Map<String, dynamic>>()
          .map(NotificationModel.fromJson)
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Marquer comme lue
  // ---------------------------------------------------------------------------

  /// PATCH /notifications/{id}/read — marque une notification comme lue.
  Future<void> markAsRead(String id) async {
    try {
      await _api.patch('/notifications/$id/read');
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Tout marquer comme lu
  // ---------------------------------------------------------------------------

  /// PATCH /notifications/read-all — marque toutes les notifications comme lues.
  Future<void> markAllAsRead() async {
    try {
      await _api.patch('/notifications/read-all');
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Supprimer une notification
  // ---------------------------------------------------------------------------

  /// DELETE /notifications/{id} — supprime définitivement une notification.
  Future<void> deleteNotification(String id) async {
    try {
      await _api.delete('/notifications/$id');
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final notificationsRepositoryProvider =
    Provider<NotificationsRepository>((ref) {
  return NotificationsRepository(ref.read(apiClientProvider));
});
