import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/notification_model.dart';
import '../providers/notifications_provider.dart';

// ---------------------------------------------------------------------------
// Écran principal
// ---------------------------------------------------------------------------

/// Écran des notifications in-app.
///
/// - Liste scrollable avec swipe-to-delete (Dismissible).
/// - Tap sur un item → marque comme lu.
/// - Bouton "Tout marquer comme lu" dans l'AppBar.
/// - États : chargement (shimmer), vide, erreur.
class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(notificationsProvider);

    return Scaffold(
      backgroundColor: AppTheme.bgPage,
      appBar: AppBar(
        title: const Text(
          'Notifications',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w700,
            color: AppTheme.textPrimary,
          ),
        ),
        backgroundColor: AppTheme.bgCard,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        actions: [
          async.whenOrNull(
            data: (state) => state.unreadCount > 0
                ? TextButton.icon(
                    onPressed: () =>
                        ref.read(notificationsProvider.notifier).markAllAsRead(),
                    icon: const Icon(Icons.done_all, size: 18),
                    label: const Text('Tout lire'),
                    style: TextButton.styleFrom(
                      foregroundColor: AppTheme.primary,
                      textStyle: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  )
                : const SizedBox.shrink(),
          ) ??
              const SizedBox.shrink(),
          const SizedBox(width: 4),
        ],
      ),
      body: async.when(
        loading: () => const _NotificationsSkeleton(),
        error: (e, _) => _NotificationsError(
          message: e.toString(),
          onRetry: () => ref.read(notificationsProvider.notifier).refresh(),
        ),
        data: (state) {
          if (state.notifications.isEmpty) {
            return const _NotificationsEmpty();
          }
          return RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: () =>
                ref.read(notificationsProvider.notifier).refresh(),
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(vertical: 8),
              itemCount: state.notifications.length,
              separatorBuilder: (context, index) => const SizedBox(height: 1),
              itemBuilder: (context, index) {
                final notif = state.notifications[index];
                return _NotificationTile(notification: notif);
              },
            ),
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Tuile notification
// ---------------------------------------------------------------------------

class _NotificationTile extends ConsumerWidget {
  const _NotificationTile({required this.notification});

  final NotificationModel notification;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isUnread = !notification.isRead;

    return Dismissible(
      key: ValueKey(notification.id),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        color: AppTheme.error,
        child: const Icon(Icons.delete_outline, color: Colors.white, size: 26),
      ),
      onDismissed: (_) {
        ref.read(notificationsProvider.notifier).delete(notification.id);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Notification supprimée'),
            duration: Duration(seconds: 2),
          ),
        );
      },
      child: InkWell(
        onTap: () {
          if (isUnread) {
            ref
                .read(notificationsProvider.notifier)
                .markAsRead(notification.id);
          }
        },
        child: Container(
          color: isUnread
              ? AppTheme.primary.withAlpha(12)
              : AppTheme.bgCard,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Icône selon le type
              _NotificationIcon(type: notification.shortType),
              const SizedBox(width: 12),
              // Contenu
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            notification.data.title,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: isUnread
                                  ? FontWeight.w700
                                  : FontWeight.w500,
                              color: AppTheme.textPrimary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _formatDate(notification.createdAt),
                          style: const TextStyle(
                            fontSize: 11,
                            color: AppTheme.textHint,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      notification.data.body,
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppTheme.textSecondary,
                        height: 1.4,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    // Montant si crédit
                    if (notification.data.amount != null) ...[
                      const SizedBox(height: 6),
                      _AmountChip(rawAmount: notification.data.amount!),
                    ],
                  ],
                ),
              ),
              // Point bleu si non lu
              if (isUnread) ...[
                const SizedBox(width: 8),
                const _UnreadDot(),
              ],
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(String iso) {
    if (iso.isEmpty) return '';
    try {
      final dt = DateTime.parse(iso).toLocal();
      return Formatters.relativeTime(dt);
    } catch (_) {
      return '';
    }
  }
}

// ---------------------------------------------------------------------------
// Icône selon le type de notification
// ---------------------------------------------------------------------------

class _NotificationIcon extends StatelessWidget {
  const _NotificationIcon({required this.type});

  final String type;

  @override
  Widget build(BuildContext context) {
    final (icon, bgColor, iconColor) = _iconData(type);

    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Icon(icon, color: iconColor, size: 22),
    );
  }

  (IconData, Color, Color) _iconData(String type) {
    final lower = type.toLowerCase();
    if (lower.contains('credit') || lower.contains('earn')) {
      return (
        Icons.attach_money,
        const Color(0xFF00C853).withAlpha(25),
        const Color(0xFF00C853),
      );
    }
    if (lower.contains('campaign') || lower.contains('ad')) {
      return (
        Icons.campaign_outlined,
        AppTheme.primary.withAlpha(25),
        AppTheme.primary,
      );
    }
    if (lower.contains('withdrawal') || lower.contains('retrait') ||
        lower.contains('wallet')) {
      return (
        Icons.account_balance_outlined,
        AppTheme.warning.withAlpha(25),
        AppTheme.warning,
      );
    }
    if (lower.contains('kyc') || lower.contains('verification')) {
      return (
        Icons.verified_user_outlined,
        Colors.blue.withAlpha(25),
        Colors.blue,
      );
    }
    // Défaut
    return (
      Icons.notifications_outlined,
      AppTheme.textHint.withAlpha(25),
      AppTheme.textSecondary,
    );
  }
}

// ---------------------------------------------------------------------------
// Chip montant
// ---------------------------------------------------------------------------

class _AmountChip extends StatelessWidget {
  const _AmountChip({required this.rawAmount});

  final String rawAmount;

  @override
  Widget build(BuildContext context) {
    final parsed = int.tryParse(rawAmount) ?? 0;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: const Color(0xFF00C853).withAlpha(20),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        '+${Formatters.currency(parsed)}',
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: Color(0xFF00C853),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Point indicateur non lu
// ---------------------------------------------------------------------------

class _UnreadDot extends StatelessWidget {
  const _UnreadDot();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 8,
      height: 8,
      margin: const EdgeInsets.only(top: 4),
      decoration: const BoxDecoration(
        color: AppTheme.primary,
        shape: BoxShape.circle,
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// État vide
// ---------------------------------------------------------------------------

class _NotificationsEmpty extends StatelessWidget {
  const _NotificationsEmpty();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.notifications_off_outlined,
              size: 72,
              color: AppTheme.textHint,
            ),
            const SizedBox(height: 20),
            const Text(
              'Aucune notification',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Vous serez notifié lorsque vous recevrez\ndes crédits ou des mises à jour.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: AppTheme.textSecondary,
                height: 1.5,
              ),
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

class _NotificationsSkeleton extends StatelessWidget {
  const _NotificationsSkeleton();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFE0E0E0),
      highlightColor: const Color(0xFFF5F5F5),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 6,
        itemBuilder: (context, index) => Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 14,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Container(
                      height: 12,
                      width: 200,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                  ],
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
// État d'erreur
// ---------------------------------------------------------------------------

class _NotificationsError extends StatelessWidget {
  const _NotificationsError({
    required this.message,
    required this.onRetry,
  });

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
            const Icon(
              Icons.wifi_off_outlined,
              color: AppTheme.error,
              size: 56,
            ),
            const SizedBox(height: 16),
            const Text(
              'Impossible de charger les notifications',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.textSecondary,
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Réessayer'),
              style: ElevatedButton.styleFrom(
                minimumSize: const Size(160, 48),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
