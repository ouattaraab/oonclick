import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';

/// Central configuration for the oon.click application.
/// Values are injected at build time via --dart-define.
class AppConfig {
  AppConfig._();

  // ---------------------------------------------------------------------------
  // API / Backend
  // ---------------------------------------------------------------------------

  static const String _envUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: '',
  );

  /// Base URL of the Laravel REST API.
  /// On Android emulator, localhost is remapped to 10.0.2.2.
  /// Override with: --dart-define=API_URL=https://api.oon.click/api
  /// Production API URL.
  static const String _productionUrl = 'https://oonclick.com/api';

  static String get baseUrl {
    if (_envUrl.isNotEmpty) return _envUrl;
    // Debug mode: use local server
    if (kDebugMode) {
      if (!kIsWeb && Platform.isAndroid) {
        return 'http://10.0.2.2:8000/api';
      }
      return 'http://localhost:8000/api';
    }
    // Release mode: production
    return _productionUrl;
  }

  /// Verify HTTPS in release mode to prevent accidental HTTP in production.
  static void assertSecureConfig() {
    if (kReleaseMode && !baseUrl.startsWith('https://')) {
      throw StateError('API_URL doit utiliser HTTPS en production: $baseUrl');
    }
  }

  // ---------------------------------------------------------------------------
  // Pusher / Real-time
  // ---------------------------------------------------------------------------

  /// Pusher application key.
  /// Override with: --dart-define=PUSHER_KEY=your_key
  static const String pusherKey = String.fromEnvironment(
    'PUSHER_KEY',
    defaultValue: '',
  );

  /// Pusher cluster (e.g. eu, ap2, us2).
  /// Override with: --dart-define=PUSHER_CLUSTER=eu
  static const String pusherCluster = String.fromEnvironment(
    'PUSHER_CLUSTER',
    defaultValue: 'eu',
  );

  // ---------------------------------------------------------------------------
  // CDN
  // ---------------------------------------------------------------------------

  /// CDN base URL for media assets.
  /// Override with: --dart-define=CDN_URL=https://cdn.oon.click
  static const String cdnUrl = String.fromEnvironment(
    'CDN_URL',
    defaultValue: '',
  );

  // ---------------------------------------------------------------------------
  // Business rules — mirror the Laravel configuration
  // ---------------------------------------------------------------------------

  /// Amount (FCFA) credited to a subscriber per ad view.
  static const int subscriberEarnPerView = 60;

  /// Minimum balance (FCFA) required to initiate a withdrawal.
  static const int minWithdrawal = 5000;

  /// One-time bonus (FCFA) credited on successful registration.
  static const int signupBonus = 500;

  /// One-time bonus (FCFA) credited to both referrer and referee on signup.
  static const int referralBonus = 200;

  /// Maximum number of ad views allowed per day per subscriber.
  static const int maxViewsPerDay = 30;

  // ---------------------------------------------------------------------------
  // Timeouts
  // ---------------------------------------------------------------------------

  /// Maximum duration (seconds) to wait for a connection.
  static const int connectTimeoutSeconds = 15;

  /// Maximum duration (seconds) to wait for a full response.
  static const int receiveTimeoutSeconds = 30;

  // ---------------------------------------------------------------------------
  // Google Sign-In
  // ---------------------------------------------------------------------------

  /// OAuth 2.0 Web Client ID for Google Sign-In server-side verification.
  /// Override with: --dart-define=GOOGLE_SERVER_CLIENT_ID=your_client_id
  static const String googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
    defaultValue:
        '67863106821-gr0oksahmebjoifmek9kjsb59pbrg13u.apps.googleusercontent.com',
  );

  // ---------------------------------------------------------------------------
  // Sentry error monitoring
  // ---------------------------------------------------------------------------

  /// Sentry DSN for the Flutter application.
  /// Override with: --dart-define=SENTRY_DSN=https://xxx@sentry.io/yyy
  /// Leave empty to disable Sentry (default in debug builds).
  static const String sentryDsn = String.fromEnvironment(
    'SENTRY_DSN',
    defaultValue: '',
  );

  /// Fraction of transactions captured for Sentry performance monitoring.
  /// Override with: --dart-define=SENTRY_TRACES_SAMPLE_RATE=0.2
  static const String _sentryTracesSampleRateRaw = String.fromEnvironment(
    'SENTRY_TRACES_SAMPLE_RATE',
    defaultValue: '0.1',
  );

  static double get sentryTracesSampleRate =>
      double.tryParse(_sentryTracesSampleRateRaw) ?? 0.1;
}
