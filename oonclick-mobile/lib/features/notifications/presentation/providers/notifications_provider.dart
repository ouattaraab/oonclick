import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_exception.dart';
import '../../data/models/notification_model.dart';
import '../../data/repositories/notifications_repository.dart';

// ---------------------------------------------------------------------------
// État
// ---------------------------------------------------------------------------

/// État complet de la liste de notifications.
class NotificationsState {
  const NotificationsState({
    this.notifications = const [],
    this.unreadCount = 0,
    this.isLoading = false,
    this.error,
  });

  final List<NotificationModel> notifications;

  /// Nombre de notifications non lues.
  final int unreadCount;

  final bool isLoading;

  /// Message d'erreur éventuel.
  final String? error;

  bool get hasError => error != null;
  bool get isEmpty => notifications.isEmpty && !isLoading;

  NotificationsState copyWith({
    List<NotificationModel>? notifications,
    int? unreadCount,
    bool? isLoading,
    String? error,
    bool clearError = false,
  }) {
    return NotificationsState(
      notifications: notifications ?? this.notifications,
      unreadCount: unreadCount ?? this.unreadCount,
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : error ?? this.error,
    );
  }
}

// ---------------------------------------------------------------------------
// Notifier
// ---------------------------------------------------------------------------

/// Gère le chargement, la lecture et la suppression des notifications.
class NotificationsNotifier extends AsyncNotifier<NotificationsState> {
  @override
  Future<NotificationsState> build() async {
    return _load();
  }

  // ---------------------------------------------------------------------------
  // Chargement initial
  // ---------------------------------------------------------------------------

  Future<NotificationsState> _load() async {
    final repo = ref.read(notificationsRepositoryProvider);
    final items = await repo.getNotifications();
    final unread = items.where((n) => !n.isRead).length;
    return NotificationsState(
      notifications: items,
      unreadCount: unread,
    );
  }

  // ---------------------------------------------------------------------------
  // Rafraîchir
  // ---------------------------------------------------------------------------

  Future<void> refresh() async {
    state = const AsyncLoading();
    try {
      final next = await _load();
      state = AsyncData(next);
    } on ApiException catch (e) {
      state = AsyncError(e, StackTrace.current);
    }
  }

  // ---------------------------------------------------------------------------
  // Marquer une notification comme lue (mise à jour optimiste)
  // ---------------------------------------------------------------------------

  Future<void> markAsRead(String id) async {
    final current = state.valueOrNull;
    if (current == null) return;

    // Trouver la notification — si déjà lue, ne rien faire.
    final index = current.notifications.indexWhere((n) => n.id == id);
    if (index == -1) return;
    if (current.notifications[index].isRead) return;

    // Mise à jour optimiste immédiate.
    final timestamp = DateTime.now().toIso8601String();
    final updated = List<NotificationModel>.from(current.notifications)
      ..[index] = current.notifications[index].markRead(timestamp);

    final newUnread = updated.where((n) => !n.isRead).length;

    state = AsyncData(current.copyWith(
      notifications: updated,
      unreadCount: newUnread,
      clearError: true,
    ));

    // Synchronisation serveur (silencieuse).
    try {
      await ref.read(notificationsRepositoryProvider).markAsRead(id);
    } on ApiException {
      // Annuler la mise à jour optimiste en cas d'échec serveur.
      state = AsyncData(current);
    }
  }

  // ---------------------------------------------------------------------------
  // Tout marquer comme lu
  // ---------------------------------------------------------------------------

  Future<void> markAllAsRead() async {
    final current = state.valueOrNull;
    if (current == null) return;

    // Mise à jour optimiste : marquer toutes les notifications comme lues.
    final timestamp = DateTime.now().toIso8601String();
    final updated = current.notifications.map((n) {
      return n.isRead ? n : n.markRead(timestamp);
    }).toList();

    state = AsyncData(current.copyWith(
      notifications: updated,
      unreadCount: 0,
      clearError: true,
    ));

    try {
      await ref.read(notificationsRepositoryProvider).markAllAsRead();
    } on ApiException {
      // Annuler en cas d'échec.
      state = AsyncData(current);
    }
  }

  // ---------------------------------------------------------------------------
  // Supprimer une notification
  // ---------------------------------------------------------------------------

  Future<void> delete(String id) async {
    final current = state.valueOrNull;
    if (current == null) return;

    // Retrait local immédiat.
    final updated = current.notifications.where((n) => n.id != id).toList();
    final newUnread = updated.where((n) => !n.isRead).length;

    state = AsyncData(current.copyWith(
      notifications: updated,
      unreadCount: newUnread,
      clearError: true,
    ));

    try {
      await ref.read(notificationsRepositoryProvider).deleteNotification(id);
    } on ApiException {
      // Restaurer l'état précédent si l'appel échoue.
      state = AsyncData(current);
    }
  }
}

// ---------------------------------------------------------------------------
// Providers
// ---------------------------------------------------------------------------

final notificationsProvider =
    AsyncNotifierProvider<NotificationsNotifier, NotificationsState>(
  NotificationsNotifier.new,
);

/// Compteur de notifications non lues — utilisé pour le badge dans la nav.
final unreadCountProvider = Provider<int>((ref) {
  return ref.watch(notificationsProvider).valueOrNull?.unreadCount ?? 0;
});
