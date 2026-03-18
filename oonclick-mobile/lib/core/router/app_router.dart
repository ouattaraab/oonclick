import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/providers/auth_provider.dart';
import '../../features/auth/presentation/screens/complete_profile_screen.dart';
import '../../features/auth/presentation/screens/otp_verification_screen.dart';
import '../../features/auth/presentation/screens/register_screen.dart';
import '../../features/auth/presentation/screens/splash_screen.dart';
import '../../features/feed/presentation/screens/ad_player_screen.dart';
import '../../features/feed/presentation/screens/feed_screen.dart';
import '../../features/notifications/presentation/screens/notifications_screen.dart';
import '../../features/profile/presentation/screens/profile_screen.dart';
import '../../features/wallet/presentation/screens/wallet_screen.dart';
import '../../shared/widgets/error_screen.dart';
import '../../shared/widgets/main_scaffold.dart';

// ---------------------------------------------------------------------------
// Router provider
// ---------------------------------------------------------------------------

/// Creates and configures the application [GoRouter].
///
/// Redirect logic:
/// - `/splash`                     → always allowed (initial bootstrap route)
/// - `/auth/**`                    → allowed only when unauthenticated
/// - all other routes              → allowed only when authenticated
/// - authenticated + no profile    → redirect to `/auth/complete-profile`
final routerProvider = Provider<GoRouter>((ref) {
  // Bridge Riverpod state to the ChangeNotifier that GoRouter watches.
  final authNotifier = _AuthChangeNotifier(ref);

  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: authNotifier,
    redirect: (context, state) {
      // Use the synchronous convenience provider so the redirect is instant.
      final authState = ref.read(authStateProvider);
      final location = state.matchedLocation;

      final isSplash = location == '/splash';
      final isAuthRoute = location.startsWith('/auth');

      // Never redirect away from the splash — SplashScreen drives navigation.
      if (isSplash) return null;

      // Unauthenticated user trying to reach a protected route.
      if (!authState.isAuthenticated && !isAuthRoute) {
        return '/auth/register';
      }

      // Authenticated user hitting an auth route (e.g. hardware back-button).
      if (authState.isAuthenticated && isAuthRoute) {
        final user = authState.user;
        // Subscriber with no name set → still needs to complete profile.
        if (user != null && user.isSubscriber && user.name == null) {
          return '/auth/complete-profile';
        }
        return '/feed';
      }

      // Authenticated subscriber who hasn't finished their profile.
      if (authState.isAuthenticated) {
        final user = authState.user;
        if (user != null &&
            user.isSubscriber &&
            user.name == null &&
            location != '/auth/complete-profile') {
          return '/auth/complete-profile';
        }
      }

      return null; // No redirect needed.
    },
    routes: [
      // ---- Splash ----
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),

      // ---- Auth flow ----
      GoRoute(
        path: '/auth',
        redirect: (context, state) => '/auth/register',
        routes: [
          GoRoute(
            path: 'register',
            builder: (context, state) => const RegisterScreen(),
          ),
          GoRoute(
            path: 'verify-otp',
            builder: (context, state) {
              // RegisterScreen passes a Map via `extra`:
              // { 'phone': '+2250701234567', 'type': 'register' }
              final extra = state.extra as Map<String, dynamic>? ?? {};
              return OtpVerificationScreen(
                phone: extra['phone'] as String? ?? '',
                type: extra['type'] as String? ?? 'register',
              );
            },
          ),
          GoRoute(
            path: 'complete-profile',
            builder: (context, state) => const CompleteProfileScreen(),
          ),
        ],
      ),

      // ---- Main shell (bottom navigation) ----
      ShellRoute(
        builder: (context, state, child) => MainScaffold(child: child),
        routes: [
          GoRoute(
            path: '/feed',
            builder: (context, state) => const FeedScreen(),
          ),
          GoRoute(
            path: '/wallet',
            builder: (context, state) => const WalletScreen(),
          ),
          GoRoute(
            path: '/notifications',
            builder: (context, state) => const NotificationsScreen(),
          ),
          GoRoute(
            path: '/profile',
            builder: (context, state) => const ProfileScreen(),
          ),
        ],
      ),

      // ---- Ad player (full-screen, outside the shell) ----
      GoRoute(
        path: '/ad/:id',
        builder: (context, state) {
          final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
          return AdPlayerScreen(campaignId: id);
        },
      ),
    ],

    // ---- Error fallback ----
    errorBuilder: (context, state) =>
        ErrorScreen(error: state.error?.toString() ?? 'Page introuvable'),
  );
});

// ---------------------------------------------------------------------------
// ChangeNotifier bridge
// ---------------------------------------------------------------------------

/// Bridges Riverpod's [authStateProvider] to [ChangeNotifier] so that
/// [GoRouter.refreshListenable] re-evaluates redirects whenever the auth
/// state changes.
class _AuthChangeNotifier extends ChangeNotifier {
  _AuthChangeNotifier(Ref ref) {
    _subscription = ref.listen<AuthState>(authStateProvider, (prev, next) {
      notifyListeners();
    });
  }

  late final ProviderSubscription<AuthState> _subscription;

  @override
  void dispose() {
    _subscription.close();
    super.dispose();
  }
}
