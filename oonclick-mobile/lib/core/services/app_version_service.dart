import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../config/app_config.dart';

// ---------------------------------------------------------------------------
// AppVersionCheckResult
// ---------------------------------------------------------------------------

class AppVersionCheckResult {
  const AppVersionCheckResult({
    required this.latestVersion,
    required this.minVersion,
    required this.forceUpdate,
    required this.requiresUpdate,
    this.storeUrl,
    this.releaseNotes,
  });

  final String latestVersion;
  final String minVersion;

  /// Whether the backend has enabled force_update for this platform.
  final bool forceUpdate;

  /// True when force_update is enabled AND current version < min_version.
  final bool requiresUpdate;

  final String? storeUrl;
  final String? releaseNotes;
}

// ---------------------------------------------------------------------------
// AppVersionService
// ---------------------------------------------------------------------------

/// Checks whether the running app version satisfies the platform's minimum
/// required version declared in the backend (`AppVersion` model).
///
/// Uses a bare Dio instance — no auth token required (public endpoint).
class AppVersionService {
  static final Dio _dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
      headers: const {'Accept': 'application/json'},
    ),
  );

  /// Returns an [AppVersionCheckResult] when the backend responds, or `null`
  /// on any network / parsing failure (allowing the app to proceed normally).
  static Future<AppVersionCheckResult?> checkForUpdate() async {
    try {
      final platform = _platform();
      if (platform == null) return null; // web / desktop — skip check

      final info = await PackageInfo.fromPlatform();
      final currentVersion = info.version;

      final response = await _dio.get<Map<String, dynamic>>(
        '/app/version',
        queryParameters: {'platform': platform, 'version': currentVersion},
      );

      final data = response.data;
      if (data == null) return null;

      final minVersion = data['min_version'] as String? ?? '0.0.0';
      final forceUpdate = data['force_update'] as bool? ?? false;
      final requiresUpdate =
          forceUpdate && _compareVersions(currentVersion, minVersion) < 0;

      return AppVersionCheckResult(
        latestVersion:
            data['latest_version'] as String? ?? currentVersion,
        minVersion: minVersion,
        forceUpdate: forceUpdate,
        requiresUpdate: requiresUpdate,
        storeUrl: data['store_url'] as String?,
        releaseNotes: data['release_notes'] as String?,
      );
    } catch (_) {
      // Network error, server not reachable, etc. — allow the user to proceed.
      return null;
    }
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  static String? _platform() {
    if (defaultTargetPlatform == TargetPlatform.android) return 'android';
    if (defaultTargetPlatform == TargetPlatform.iOS) return 'ios';
    return null;
  }

  /// Semantic version comparison.
  /// Returns negative  if [a] < [b], 0 if equal, positive if [a] > [b].
  static int _compareVersions(String a, String b) {
    final partsA = a.split('.').map((s) => int.tryParse(s) ?? 0).toList();
    final partsB = b.split('.').map((s) => int.tryParse(s) ?? 0).toList();

    for (var i = 0; i < 3; i++) {
      final pa = i < partsA.length ? partsA[i] : 0;
      final pb = i < partsB.length ? partsB[i] : 0;
      if (pa != pb) return pa - pb;
    }
    return 0;
  }
}
