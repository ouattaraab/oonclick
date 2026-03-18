import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/notifications/presentation/widgets/notification_badge.dart';

/// Shell scaffold qui enveloppe les routes de la navigation principale.
///
/// [child] est le widget de la route active, fourni par [ShellRoute].
class MainScaffold extends ConsumerWidget {
  const MainScaffold({super.key, required this.child});

  final Widget child;

  int _activeIndex(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final paths = ['/feed', '/wallet', '/notifications', '/profile'];
    final idx = paths.indexWhere((p) => location.startsWith(p));
    return idx == -1 ? 0 : idx;
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final activeIdx = _activeIndex(context);

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: activeIdx,
        onDestinationSelected: (i) {
          const paths = ['/feed', '/wallet', '/notifications', '/profile'];
          context.go(paths[i]);
        },
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.play_circle_outline),
            selectedIcon: Icon(Icons.play_circle),
            label: 'Feed',
          ),
          const NavigationDestination(
            icon: Icon(Icons.account_balance_wallet_outlined),
            selectedIcon: Icon(Icons.account_balance_wallet),
            label: 'Wallet',
          ),
          NavigationDestination(
            icon: NotificationBadge(
              child: const Icon(Icons.notifications_outlined),
            ),
            selectedIcon: NotificationBadge(
              child: const Icon(Icons.notifications),
            ),
            label: 'Notifs',
          ),
          const NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
