import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/providers/auth_provider.dart';
import '../../features/auth/presentation/screens/complete_profile_screen.dart';
import '../../features/auth/presentation/screens/otp_verification_screen.dart';
import '../../features/auth/presentation/screens/register_screen.dart';
import '../../features/auth/presentation/screens/splash_screen.dart';
import '../../features/feed/presentation/screens/ad_player_screen.dart';
import '../../features/feed/presentation/screens/ad_history_screen.dart';
import '../../features/feed/presentation/screens/feed_screen.dart';
import '../../features/notifications/presentation/screens/notifications_screen.dart';
import '../../features/profile/presentation/screens/profile_screen.dart';
import '../../features/profile/presentation/screens/referral_screen.dart';
import '../../features/profile/presentation/screens/settings_screen.dart';
import '../../features/campaigns/presentation/screens/campaign_detail_screen.dart';
import '../../features/campaigns/presentation/screens/campaign_form_screen.dart';
import '../../features/campaigns/presentation/screens/campaigns_list_screen.dart';
import '../../features/wallet/presentation/screens/wallet_screen.dart';
import '../../features/wallet/presentation/screens/withdrawal_screen.dart';
import '../../features/wallet/presentation/screens/withdrawal_history_screen.dart';
import '../../features/checkin/presentation/screens/checkin_screen.dart';
import '../../features/gamification/presentation/screens/gamification_screen.dart';
import '../../features/gamification/presentation/screens/leaderboard_screen.dart';
import '../../features/kyc/presentation/screens/kyc_screen.dart';
import '../../features/profile/presentation/screens/edit_profile_screen.dart';
import '../../features/profile/presentation/screens/change_phone_screen.dart';
import '../../features/profile/presentation/screens/security_screen.dart';
import '../../features/profile/presentation/screens/privacy_screen.dart';
import '../../features/profile/presentation/screens/help_support_screen.dart';
import '../../features/legal/presentation/screens/legal_webview_screen.dart';
import '../../features/surveys/presentation/screens/survey_list_screen.dart';
import '../../features/surveys/presentation/screens/survey_screen.dart';
import '../../features/missions/presentation/screens/missions_screen.dart';
import '../../features/offers/data/models/offer_model.dart';
import '../../features/offers/presentation/screens/offers_screen.dart';
import '../../features/offers/presentation/screens/claim_screen.dart';
import '../../features/coupons/presentation/screens/coupons_screen.dart';
import '../../shared/widgets/error_screen.dart';
import '../../shared/widgets/main_scaffold.dart';

// ---------------------------------------------------------------------------
// Router provider
// ---------------------------------------------------------------------------

/// Global navigator key shared with [FcmService] so push-notification taps
/// can navigate even outside the widget tree.
final rootNavigatorKey = GlobalKey<NavigatorState>();

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
    navigatorKey: rootNavigatorKey,
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
        path: '/auth/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/auth/verify-otp',
        builder: (context, state) {
          final extra = state.extra as Map<String, dynamic>? ?? {};
          return OtpVerificationScreen(
            phone: extra['phone'] as String? ?? '',
            type: extra['type'] as String? ?? 'register',
            method: extra['method'] as String? ?? 'phone',
            email: extra['email'] as String?,
            verificationId: extra['verificationId'] as String?,
          );
        },
      ),
      GoRoute(
        path: '/auth/complete-profile',
        builder: (context, state) => const CompleteProfileScreen(),
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
          final extra = state.extra as Map<String, dynamic>? ?? {};
          final isReplay = extra['replay'] == true;
          return AdPlayerScreen(campaignId: id, isReplay: isReplay);
        },
      ),

      // ---- Ad history ----
      GoRoute(
        path: '/ads/history',
        builder: (context, state) => const AdHistoryScreen(),
      ),

      // ---- Withdrawal (full-screen, outside the shell) ----
      GoRoute(
        path: '/withdrawal',
        builder: (context, state) => const WithdrawalScreen(),
      ),
      GoRoute(
        path: '/wallet/history',
        builder: (context, state) => const WithdrawalHistoryScreen(),
      ),

      // ---- Profile sub-screens (full-screen, outside the shell) ----
      GoRoute(
        path: '/profile/settings',
        builder: (context, state) => const SettingsScreen(),
      ),
      GoRoute(
        path: '/profile/referral',
        builder: (context, state) => const ReferralScreen(),
      ),
      GoRoute(
        path: '/profile/edit',
        builder: (context, state) => const EditProfileScreen(),
      ),
      GoRoute(
        path: '/profile/change-phone',
        builder: (context, state) => const ChangePhoneScreen(),
      ),
      GoRoute(
        path: '/profile/security',
        builder: (context, state) => const SecurityScreen(),
      ),
      GoRoute(
        path: '/profile/privacy',
        builder: (context, state) => const PrivacyScreen(),
      ),
      GoRoute(
        path: '/profile/help',
        builder: (context, state) => const HelpSupportScreen(),
      ),
      GoRoute(
        path: '/profile/notifications',
        builder: (context, state) => const NotificationsScreen(),
      ),

      // ---- Legal pages ----
      GoRoute(
        path: '/legal/cgu',
        builder: (context, state) => const LegalWebviewScreen(
          title: 'Conditions Générales d\'Utilisation',
          path: '/cgu',
        ),
      ),
      GoRoute(
        path: '/legal/privacy',
        builder: (context, state) => const LegalWebviewScreen(
          title: 'Politique de Confidentialité',
          path: '/confidentialite',
        ),
      ),

      // ---- Check-in ----
      GoRoute(
        path: '/checkin',
        builder: (context, state) => const CheckinScreen(),
      ),

      // ---- Gamification ----
      GoRoute(
        path: '/gamification',
        builder: (context, state) => const GamificationScreen(),
      ),
      GoRoute(
        path: '/leaderboard',
        builder: (context, state) => const LeaderboardScreen(),
      ),

      // ---- KYC ----
      GoRoute(
        path: '/kyc',
        builder: (context, state) => const KycScreen(),
      ),

      // ---- Campaigns (advertiser) ----
      GoRoute(
        path: '/campaigns',
        builder: (context, state) => const CampaignsListScreen(),
      ),
      GoRoute(
        path: '/campaigns/new',
        builder: (context, state) => const CampaignFormScreen(),
      ),
      GoRoute(
        path: '/campaigns/:id',
        builder: (context, state) {
          final id =
              int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
          return CampaignDetailScreen(campaignId: id);
        },
      ),
      GoRoute(
        path: '/campaigns/:id/edit',
        builder: (context, state) {
          // The existing campaign object is passed via GoRouter `extra`.
          final campaign = state.extra as dynamic;
          return CampaignFormScreen(existingCampaign: campaign);
        },
      ),

      // ---- Surveys ----
      GoRoute(
        path: '/surveys',
        builder: (context, state) => const SurveyListScreen(),
      ),
      GoRoute(
        path: '/surveys/:id',
        builder: (context, state) {
          final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
          return SurveyScreen(surveyId: id);
        },
      ),

      // ---- Missions ----
      GoRoute(
        path: '/missions',
        builder: (context, state) => const MissionsScreen(),
      ),

      // ---- Offres cashback (Phase 3 — Feature 5) ----
      GoRoute(
        path: '/offers',
        builder: (context, state) => const OffersScreen(),
      ),
      GoRoute(
        path: '/offers/:id/claim',
        builder: (context, state) {
          final offer = state.extra as OfferModel?;
          if (offer == null) return const OffersScreen();
          return ClaimScreen(offer: offer);
        },
      ),

      // ---- Coupons (Phase 3 — Feature 5) ----
      GoRoute(
        path: '/coupons',
        builder: (context, state) => const CouponsScreen(),
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
