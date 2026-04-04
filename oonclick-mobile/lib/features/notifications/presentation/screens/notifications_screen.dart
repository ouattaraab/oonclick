import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shimmer/shimmer.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/notification_model.dart';
import '../providers/notifications_provider.dart';

// ---------------------------------------------------------------------------
// Écran principal
// ---------------------------------------------------------------------------

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(notificationsProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // Navy top bar
          Container(
            color: AppColors.navy,
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 8, 14, 12),
                child: Row(
                  children: [
                    Text(
                      'Notifications',
                      style: GoogleFonts.nunito(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const Spacer(),
                    async.whenOrNull(
                          data: (state) => state.unreadCount > 0
                              ? GestureDetector(
                                  onTap: () => ref
                                      .read(notificationsProvider.notifier)
                                      .markAllAsRead(),
                                  child: Text(
                                    'Tout marquer lu',
                                    style: GoogleFonts.nunito(
                                      color: AppColors.skyMid,
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                )
                              : const SizedBox.shrink(),
                        ) ??
                        const SizedBox.shrink(),
                  ],
                ),
              ),
            ),
          ),
          // Body
          Expanded(
            child: async.when(
              loading: () => const _NotificationsSkeleton(),
              error: (e, _) => _NotificationsError(
                message: e.toString(),
                onRetry: () =>
                    ref.read(notificationsProvider.notifier).refresh(),
              ),
              data: (state) {
                if (state.notifications.isEmpty) {
                  return const _NotificationsEmpty();
                }
                return RefreshIndicator(
                  color: AppColors.sky,
                  onRefresh: () =>
                      ref.read(notificationsProvider.notifier).refresh(),
                  child: _GroupedList(
                    notifications: state.notifications,
                    ref: ref,
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
// Grouped list with section labels
// ---------------------------------------------------------------------------

class _GroupedList extends StatelessWidget {
  const _GroupedList({required this.notifications, required this.ref});

  final List<NotificationModel> notifications;
  final WidgetRef ref;

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final yesterday = today.subtract(const Duration(days: 1));
    final weekStart = today.subtract(const Duration(days: 7));

    final todayList = <NotificationModel>[];
    final yesterdayList = <NotificationModel>[];
    final weekList = <NotificationModel>[];
    final olderList = <NotificationModel>[];

    for (final n in notifications) {
      final dt = _parseDate(n.createdAt);
      final day = DateTime(dt.year, dt.month, dt.day);
      if (!day.isBefore(today)) {
        todayList.add(n);
      } else if (!day.isBefore(yesterday)) {
        yesterdayList.add(n);
      } else if (!day.isBefore(weekStart)) {
        weekList.add(n);
      } else {
        olderList.add(n);
      }
    }

    final items = <Widget>[];
    void addSection(String label, List<NotificationModel> list) {
      if (list.isEmpty) return;
      items.add(_SectionLabel(label: label));
      for (final n in list) {
        items.add(_NotificationTile(notification: n, ref: ref));
      }
    }

    addSection("Aujourd'hui", todayList);
    addSection('Hier', yesterdayList);
    addSection('Cette semaine', weekList);
    addSection('Plus ancien', olderList);

    return ListView(children: items);
  }

  DateTime _parseDate(String iso) {
    try {
      return DateTime.parse(iso).toLocal();
    } catch (_) {
      return DateTime.now();
    }
  }
}

// ---------------------------------------------------------------------------
// Section label
// ---------------------------------------------------------------------------

class _SectionLabel extends StatelessWidget {
  const _SectionLabel({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
      decoration: BoxDecoration(
        color: AppColors.bg,
        border: Border(
          bottom: BorderSide(color: AppColors.border, width: 1),
        ),
      ),
      child: Text(
        label.toUpperCase(),
        style: GoogleFonts.nunito(
          fontSize: 10,
          fontWeight: FontWeight.w800,
          color: AppColors.muted,
          letterSpacing: 0.4,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Notification tile
// ---------------------------------------------------------------------------

class _NotificationTile extends ConsumerWidget {
  const _NotificationTile({required this.notification, required this.ref});

  final NotificationModel notification;
  final WidgetRef ref;

  @override
  Widget build(BuildContext context, WidgetRef widgetRef) {
    final isUnread = !notification.isRead;

    return Dismissible(
      key: ValueKey(notification.id),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        color: AppColors.danger,
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
          showModalBottomSheet<void>(
            context: context,
            isScrollControlled: true,
            backgroundColor: Colors.transparent,
            builder: (_) => _NotificationDetailSheet(notification: notification),
          );
        },
        child: Container(
          color: isUnread
              ? AppColors.sky.withAlpha(10)
              : Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
          decoration: BoxDecoration(
            border: Border(
              bottom: BorderSide(color: AppColors.border, width: 1),
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _NotifIcon(type: notification.shortType),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      notification.data.title,
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        fontWeight:
                            isUnread ? FontWeight.w800 : FontWeight.w700,
                        color: AppColors.navy,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      notification.data.body,
                      style: GoogleFonts.nunito(
                        fontSize: 11,
                        color: AppColors.muted,
                        fontWeight: FontWeight.w600,
                        height: 1.35,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (notification.data.amount != null) ...[
                      const SizedBox(height: 4),
                      _AmountChip(rawAmount: notification.data.amount!),
                    ],
                    const SizedBox(height: 3),
                    Text(
                      _formatDate(notification.createdAt),
                      style: GoogleFonts.nunito(
                        fontSize: 9,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              if (isUnread) ...[
                const SizedBox(width: 8),
                Container(
                  width: 7,
                  height: 7,
                  margin: const EdgeInsets.only(top: 5),
                  decoration: const BoxDecoration(
                    color: AppColors.sky,
                    shape: BoxShape.circle,
                  ),
                ),
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
      return Formatters.relativeTime(DateTime.parse(iso).toLocal());
    } catch (_) {
      return '';
    }
  }
}

// ---------------------------------------------------------------------------
// Notification icon — 36px circle, color-coded
// ---------------------------------------------------------------------------

class _NotifIcon extends StatelessWidget {
  const _NotifIcon({required this.type});

  final String type;

  @override
  Widget build(BuildContext context) {
    final (emoji, bg) = _data(type);
    return Container(
      width: 36,
      height: 36,
      decoration: BoxDecoration(color: bg, shape: BoxShape.circle),
      child: Center(
        child: Text(emoji, style: const TextStyle(fontSize: 17)),
      ),
    );
  }

  (String, Color) _data(String type) {
    final t = type.toLowerCase();
    if (t.contains('credit') || t.contains('earn') || t.contains('payment')) {
      return ('💰', const Color(0xFFDCFCE7));
    }
    if (t.contains('campaign') || t.contains('ad')) {
      return ('📢', AppColors.skyPale);
    }
    if (t.contains('withdrawal') || t.contains('retrait') ||
        t.contains('wallet')) {
      return ('✅', const Color(0xFFEEF2FF));
    }
    if (t.contains('remind') || t.contains('rappel')) {
      return ('⏰', const Color(0xFFFEF3C7));
    }
    if (t.contains('kyc') || t.contains('verif')) {
      return ('🪪', const Color(0xFFEEF2FF));
    }
    if (t.contains('referral') || t.contains('parrainage') ||
        t.contains('bonus')) {
      return ('🎁', const Color(0xFFDCFCE7));
    }
    return ('👋', const Color(0xFFF3F4F6));
  }
}

// ---------------------------------------------------------------------------
// Amount chip
// ---------------------------------------------------------------------------

class _AmountChip extends StatelessWidget {
  const _AmountChip({required this.rawAmount});

  final String rawAmount;

  @override
  Widget build(BuildContext context) {
    final parsed = int.tryParse(rawAmount) ?? 0;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: AppColors.success.withAlpha(20),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        '+${Formatters.currency(parsed)}',
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: AppColors.success,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Notification detail bottom sheet
// ---------------------------------------------------------------------------

class _NotificationDetailSheet extends StatelessWidget {
  const _NotificationDetailSheet({required this.notification});

  final NotificationModel notification;

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.7,
      minChildSize: 0.4,
      maxChildSize: 0.9,
      builder: (context, scrollController) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: ListView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          children: [
            // Drag handle
            Center(
              child: Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),

            // Icon + title
            Center(
              child: Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: AppColors.skyPale,
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    _iconEmoji(notification.shortType),
                    style: const TextStyle(fontSize: 26),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 12),
            Text(
              notification.data.title,
              style: GoogleFonts.nunito(
                fontSize: 16,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 4),
            Text(
              _formatDate(notification.createdAt),
              style: GoogleFonts.nunito(
                fontSize: 11,
                color: AppColors.muted,
              ),
              textAlign: TextAlign.center,
            ),

            const SizedBox(height: 16),

            // Amount if present
            if (notification.data.amount != null) ...[
              Container(
                padding: const EdgeInsets.symmetric(vertical: 14),
                decoration: BoxDecoration(
                  color: AppColors.successLight,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '+${Formatters.currency(int.tryParse(notification.data.amount!) ?? 0)}',
                    style: GoogleFonts.nunito(
                      fontSize: 24,
                      fontWeight: FontWeight.w900,
                      color: AppColors.success,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 12),
            ],

            // Body
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.bg,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Text(
                notification.data.body,
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  color: AppColors.muted,
                  height: 1.6,
                ),
              ),
            ),

            const SizedBox(height: 20),

            // Close button
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: () => Navigator.of(context).pop(),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.sky,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  'Fermer',
                  style: GoogleFonts.nunito(
                    fontWeight: FontWeight.w800,
                    fontSize: 14,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _iconEmoji(String type) {
    final t = type.toLowerCase();
    if (t.contains('credit') || t.contains('earn')) return '💰';
    if (t.contains('campaign') || t.contains('ad')) return '📢';
    if (t.contains('withdrawal') || t.contains('retrait')) return '✅';
    if (t.contains('remind') || t.contains('rappel')) return '⏰';
    if (t.contains('referral') || t.contains('bonus')) return '🎁';
    return '🔔';
  }

  String _formatDate(String iso) {
    if (iso.isEmpty) return '';
    try {
      final dt = DateTime.parse(iso).toLocal();
      return '${_weekday(dt.weekday)} ${dt.day} ${_month(dt.month)} ${dt.year} · ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return '';
    }
  }

  String _weekday(int w) => const [
        '',
        'Lundi',
        'Mardi',
        'Mercredi',
        'Jeudi',
        'Vendredi',
        'Samedi',
        'Dimanche'
      ][w];

  String _month(int m) => const [
        '',
        'janv.',
        'févr.',
        'mars',
        'avr.',
        'mai',
        'juin',
        'juil.',
        'août',
        'sept.',
        'oct.',
        'nov.',
        'déc.'
      ][m];
}

// ---------------------------------------------------------------------------
// Empty state
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
              color: AppColors.muted.withAlpha(100),
            ),
            const SizedBox(height: 20),
            Text(
              'Aucune notification',
              style: GoogleFonts.nunito(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Vous serez notifié lorsque vous recevrez\ndes crédits ou des mises à jour.',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 14,
                color: AppColors.muted,
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
// Skeleton
// ---------------------------------------------------------------------------

class _NotificationsSkeleton extends StatelessWidget {
  const _NotificationsSkeleton();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFE0E0E0),
      highlightColor: const Color(0xFFF5F5F5),
      child: ListView.builder(
        padding: const EdgeInsets.all(12),
        itemCount: 6,
        itemBuilder: (context, index) => Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: const BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 12,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Container(
                      height: 10,
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
// Error state
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
            const Icon(Icons.wifi_off_outlined, color: AppColors.danger, size: 56),
            const SizedBox(height: 16),
            const Text(
              'Impossible de charger les notifications',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 13, color: AppColors.muted),
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
