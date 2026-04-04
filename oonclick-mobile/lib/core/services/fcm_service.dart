import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:go_router/go_router.dart';

// ---------------------------------------------------------------------------
// Background handler — MUST be a top-level function (not a class method).
// Firebase spawns an isolate for it, so it cannot access the widget tree.
// ---------------------------------------------------------------------------

/// Handles incoming FCM messages when the app is fully terminated or in the
/// background. Firebase invokes this in a separate isolate.
@pragma('vm:entry-point')
Future<void> fcmBackgroundHandler(RemoteMessage message) async {
  // Firebase must be initialised inside isolated background handlers.
  await Firebase.initializeApp();

  if (kDebugMode) {
    debugPrint('[FCM] Background message: ${message.notification?.title}');
    debugPrint('[FCM] Data: ${message.data}');
  }
}

// ---------------------------------------------------------------------------
// FcmService
// ---------------------------------------------------------------------------

/// Centralises all Firebase Cloud Messaging initialisation and event handling.
///
/// Call [FcmService.init] once from `main()`, after [Firebase.initializeApp].
class FcmService {
  FcmService._();

  static final FirebaseMessaging _messaging = FirebaseMessaging.instance;

  // -------------------------------------------------------------------------
  // Initialisation
  // -------------------------------------------------------------------------

  /// Bootstraps FCM:
  ///   1. Requests notification permission (required on iOS, optional on Android 13+).
  ///   2. Reads the current device token.
  ///   3. Registers token-refresh, foreground-message, and tap listeners.
  ///   4. Wires the background handler.
  ///   5. Handles the notification that opened the app from a terminated state.
  ///
  /// Returns the FCM token string, or `null` if FCM is unavailable.
  static Future<String?> init() async {
    // 1. Request permission
    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );

    if (kDebugMode) {
      debugPrint(
        '[FCM] Permission status: ${settings.authorizationStatus.name}',
      );
    }

    if (settings.authorizationStatus == AuthorizationStatus.denied) {
      if (kDebugMode) debugPrint('[FCM] Notifications denied by user.');
      return null;
    }

    // 2. Retrieve current token
    final token = await _messaging.getToken();
    if (kDebugMode) debugPrint('[FCM] Token: $token');

    // 3a. Listen for token refresh — caller (FcmApiService) should re-register
    _messaging.onTokenRefresh.listen((newToken) {
      if (kDebugMode) debugPrint('[FCM] Token refreshed: $newToken');
      // Navigation/UI context is unavailable here — post to event bus if needed.
      _onTokenRefreshController?.call(newToken);
    });

    // 3b. Foreground message handler
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      if (kDebugMode) {
        debugPrint('[FCM] Foreground message: ${message.notification?.title}');
      }
      _onMessageController?.call(message);
    });

    // 4. Background handler (top-level function required by Firebase)
    FirebaseMessaging.onBackgroundMessage(fcmBackgroundHandler);

    // 5. Tap handler: app is in background, user taps the notification
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);

    // 5b. Tap handler: app was terminated, notification opened it
    final initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleNotificationTap(initialMessage);
    }

    return token;
  }

  // -------------------------------------------------------------------------
  // Token access
  // -------------------------------------------------------------------------

  /// Returns the current FCM registration token, or `null` on failure.
  static Future<String?> getToken() => _messaging.getToken();

  /// Deletes the current FCM token (e.g. on logout before calling the API).
  static Future<void> deleteToken() => _messaging.deleteToken();

  // -------------------------------------------------------------------------
  // Callbacks (optional — wire these from your app layer)
  // -------------------------------------------------------------------------

  /// Called when a foreground [RemoteMessage] arrives.
  static void Function(RemoteMessage)? _onMessageController;

  /// Called when the FCM token is refreshed.
  static void Function(String)? _onTokenRefreshController;

  /// Register a callback invoked on foreground messages.
  static void onMessage(void Function(RemoteMessage) handler) {
    _onMessageController = handler;
  }

  /// Register a callback invoked when the token is refreshed.
  static void onTokenRefresh(void Function(String) handler) {
    _onTokenRefreshController = handler;
  }

  // -------------------------------------------------------------------------
  // Navigation on tap
  // -------------------------------------------------------------------------

  /// Handles notification taps by reading the [data] payload and dispatching
  /// to the appropriate screen.
  ///
  /// Expected data keys:
  ///   - `screen` : 'wallet' | 'campaigns' | 'profile' | …
  ///   - `type`   : 'credit_received' | 'campaign_approved' | …
  static void _handleNotificationTap(RemoteMessage message) {
    if (kDebugMode) {
      debugPrint('[FCM] Notification tapped: ${message.data}');
    }

    final screen = message.data['screen'] as String?;
    if (screen == null || _navigatorKey?.currentContext == null) {
      // Router not ready yet — store for later.
      _pendingDeepLink = screen;
      return;
    }
    _navigateTo(screen);
  }

  /// Navigator key set by the app (call [setNavigatorKey] from main).
  static GlobalKey<NavigatorState>? _navigatorKey;

  /// A deep-link waiting to be consumed once the router is mounted.
  static String? _pendingDeepLink;

  /// Provides the navigator key so FCM can push routes.
  static void setNavigatorKey(GlobalKey<NavigatorState> key) {
    _navigatorKey = key;
    // Consume any pending deep-link now that the router is ready.
    if (_pendingDeepLink != null) {
      _navigateTo(_pendingDeepLink!);
      _pendingDeepLink = null;
    }
  }

  /// Maps notification [screen] values to GoRouter paths and navigates.
  static void _navigateTo(String screen) {
    final ctx = _navigatorKey?.currentContext;
    if (ctx == null) return;

    const routes = {
      'wallet': '/wallet',
      'campaigns': '/feed',
      'profile': '/profile',
      'kyc': '/kyc',
      'notifications': '/notifications',
      'withdrawal': '/withdrawal',
      'gamification': '/gamification',
      'checkin': '/checkin',
    };

    final path = routes[screen];
    if (path != null) {
      GoRouter.of(ctx).push(path);
    }
  }
}
