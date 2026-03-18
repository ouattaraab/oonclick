/// Central configuration for the oon.click application.
/// Values are injected at build time via --dart-define.
class AppConfig {
  AppConfig._();

  // ---------------------------------------------------------------------------
  // API / Backend
  // ---------------------------------------------------------------------------

  /// Base URL of the Laravel REST API.
  /// Override with: --dart-define=API_URL=https://api.oon.click/api
  static const String baseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://localhost:8000/api',
  );

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

  // ---------------------------------------------------------------------------
  // Timeouts
  // ---------------------------------------------------------------------------

  /// Maximum duration (seconds) to wait for a connection.
  static const int connectTimeoutSeconds = 15;

  /// Maximum duration (seconds) to wait for a full response.
  static const int receiveTimeoutSeconds = 30;
}
