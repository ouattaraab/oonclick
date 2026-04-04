import 'package:flutter/foundation.dart';

import '../config/app_config.dart';

// ---------------------------------------------------------------------------
// SentryService
// ---------------------------------------------------------------------------
//
// Thin wrapper around Sentry that keeps the rest of the codebase decoupled
// from the SDK. When SENTRY_DSN is empty (local dev, CI) every method
// becomes a no-op, so no conditional guards are needed at call sites.
//
// SETUP
// -----
// 1. Add the dependency to pubspec.yaml:
//      sentry_flutter: ^8.x.x
//
// 2. In main.dart, replace the plain `runApp(...)` call with:
//
//      await SentryService.init(() => runApp(const OonClickApp()));
//
// 3. Pass build-time DSN via --dart-define:
//      flutter run  --dart-define=SENTRY_DSN=https://xxx@sentry.io/yyy
//      flutter build apk --dart-define=SENTRY_DSN=https://xxx@sentry.io/yyy
//
// ---------------------------------------------------------------------------

/// Indicates whether Sentry is actively enabled in the current build.
///
/// Returns `false` when [AppConfig.sentryDsn] is empty or when running in
/// [kDebugMode] without an explicit DSN — preventing noise in local logs.
bool get isSentryEnabled =>
    AppConfig.sentryDsn.isNotEmpty && !kDebugMode;

/// Central facade for Sentry operations throughout the oon.click app.
///
/// All methods silently skip when Sentry is disabled so call sites require
/// no conditional checks.
abstract final class SentryService {
  SentryService._();

  // -------------------------------------------------------------------------
  // Initialisation
  // -------------------------------------------------------------------------

  /// Wraps [runApp] with Sentry Flutter initialisation.
  ///
  /// Call this once at the top of `main()`:
  ///
  /// ```dart
  /// void main() async {
  ///   WidgetsFlutterBinding.ensureInitialized();
  ///   await SentryService.init(() => runApp(const OonClickApp()));
  /// }
  /// ```
  ///
  /// When [AppConfig.sentryDsn] is empty or we are in [kDebugMode] the
  /// [appRunner] is called directly without Sentry overhead.
  static Future<void> init(Future<void> Function() appRunner) async {
    if (!isSentryEnabled) {
      if (kDebugMode && AppConfig.sentryDsn.isNotEmpty) {
        debugPrint(
          '[SentryService] DSN fourni mais ignoré en mode debug. '
          'Ajoutez --dart-define=SENTRY_DSN=... en mode release.',
        );
      }
      await appRunner();
      return;
    }

    // The dynamic import pattern keeps the sentry_flutter package optional:
    // if the dependency is not in pubspec.yaml the app still compiles and runs
    // — Sentry is simply disabled. Add the import below once the package is
    // present in pubspec.yaml.
    //
    // import 'package:sentry_flutter/sentry_flutter.dart';
    //
    // await SentryFlutter.init(
    //   (options) {
    //     options.dsn               = AppConfig.sentryDsn;
    //     options.environment       = kReleaseMode ? 'production' : 'staging';
    //     options.tracesSampleRate   = AppConfig.sentryTracesSampleRate;
    //     options.sendDefaultPii     = false; // Ne jamais envoyer de PII
    //     options.attachScreenshot   = true;
    //     options.attachViewHierarchy = true;
    //     options.enableAutoSessionTracking = true;
    //   },
    //   appRunner: appRunner,
    // );

    // Placeholder until the package is added.
    debugPrint('[SentryService] init() — ajoutez sentry_flutter à pubspec.yaml');
    await appRunner();
  }

  // -------------------------------------------------------------------------
  // Error capture
  // -------------------------------------------------------------------------

  /// Captures an exception and optional stack trace, forwarding to Sentry.
  ///
  /// Usage:
  /// ```dart
  /// try {
  ///   await riskyOperation();
  /// } catch (e, st) {
  ///   await SentryService.captureException(e, stackTrace: st);
  ///   rethrow;
  /// }
  /// ```
  static Future<void> captureException(
    Object exception, {
    StackTrace? stackTrace,
    String? hint,
  }) async {
    if (!isSentryEnabled) {
      if (kDebugMode) {
        debugPrint('[SentryService] Exception capturée (Sentry désactivé): $exception');
        if (stackTrace != null) debugPrint(stackTrace.toString());
      }
      return;
    }

    // Uncomment when sentry_flutter is in pubspec.yaml:
    //
    // await Sentry.captureException(
    //   exception,
    //   stackTrace: stackTrace,
    //   hint: hint != null ? Hint.withMap({'message': hint}) : null,
    // );
  }

  // -------------------------------------------------------------------------
  // Message capture
  // -------------------------------------------------------------------------

  /// Captures a plain-text message with optional severity level.
  ///
  /// Useful for important non-error events (e.g. payment gateway timeouts,
  /// fraud score thresholds crossed).
  ///
  /// [level] matches Sentry severity strings: 'debug' | 'info' | 'warning' |
  /// 'error' | 'fatal'. Defaults to 'info'.
  static Future<void> captureMessage(
    String message, {
    String level = 'info',
  }) async {
    if (!isSentryEnabled) {
      if (kDebugMode) {
        debugPrint('[SentryService] Message [$level]: $message');
      }
      return;
    }

    // Uncomment when sentry_flutter is in pubspec.yaml:
    //
    // await Sentry.captureMessage(
    //   message,
    //   level: SentryLevel.fromName(level),
    // );
  }

  // -------------------------------------------------------------------------
  // User context
  // -------------------------------------------------------------------------

  /// Attaches an authenticated user's context to all subsequent Sentry events.
  ///
  /// Call after a successful login. Pass only non-PII identifiers:
  /// ```dart
  /// await SentryService.setUser(userId: 42, role: 'subscriber');
  /// ```
  static Future<void> setUser({
    required int userId,
    String? role,
  }) async {
    if (!isSentryEnabled) return;

    // Uncomment when sentry_flutter is in pubspec.yaml:
    //
    // await Sentry.configureScope((scope) {
    //   scope.setUser(SentryUser(
    //     id: userId.toString(),
    //     data: role != null ? {'role': role} : null,
    //   ));
    // });
  }

  /// Clears the user context from Sentry (call on logout).
  static Future<void> clearUser() async {
    if (!isSentryEnabled) return;

    // Uncomment when sentry_flutter is in pubspec.yaml:
    //
    // await Sentry.configureScope((scope) => scope.setUser(null));
  }

  // -------------------------------------------------------------------------
  // Breadcrumbs
  // -------------------------------------------------------------------------

  /// Adds a breadcrumb to help trace the path leading to an error.
  ///
  /// ```dart
  /// SentryService.addBreadcrumb(
  ///   message: 'Ad view started',
  ///   category: 'ad_player',
  ///   data: {'campaign_id': 12, 'format': 'quiz'},
  /// );
  /// ```
  static void addBreadcrumb({
    required String message,
    String category = 'app',
    String level = 'info',
    Map<String, dynamic>? data,
  }) {
    if (!isSentryEnabled) return;

    // Uncomment when sentry_flutter is in pubspec.yaml:
    //
    // Sentry.addBreadcrumb(Breadcrumb(
    //   message: message,
    //   category: category,
    //   level: SentryLevel.fromName(level),
    //   data: data,
    //   timestamp: DateTime.now().toUtc(),
    // ));
  }
}
