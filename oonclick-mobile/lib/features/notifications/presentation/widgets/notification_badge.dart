import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/notifications_provider.dart';

/// Enveloppe [child] et affiche un badge rouge avec le nombre de notifications
/// non lues, si celui-ci est supérieur à zéro.
///
/// Le badge affiche "9+" si le compteur dépasse 9.
///
/// Exemple d'utilisation dans `NavigationDestination` :
/// ```dart
/// NavigationDestination(
///   icon: NotificationBadge(child: Icon(Icons.notifications_outlined)),
///   selectedIcon: NotificationBadge(child: Icon(Icons.notifications)),
///   label: 'Notifs',
/// )
/// ```
class NotificationBadge extends ConsumerWidget {
  const NotificationBadge({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final count = ref.watch(unreadCountProvider);

    if (count <= 0) return child;

    return Stack(
      clipBehavior: Clip.none,
      children: [
        child,
        Positioned(
          top: -4,
          right: -6,
          child: _Badge(count: count),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Badge interne
// ---------------------------------------------------------------------------

class _Badge extends StatelessWidget {
  const _Badge({required this.count});

  final int count;

  String get _label => count > 9 ? '9+' : '$count';

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 16,
      constraints: const BoxConstraints(minWidth: 16),
      padding: const EdgeInsets.symmetric(horizontal: 4),
      decoration: const BoxDecoration(
        color: Color(0xFFF44336),
        borderRadius: BorderRadius.all(Radius.circular(8)),
      ),
      alignment: Alignment.center,
      child: Text(
        _label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 9,
          fontWeight: FontWeight.w700,
          height: 1,
        ),
      ),
    );
  }
}
