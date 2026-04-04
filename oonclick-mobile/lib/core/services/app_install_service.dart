import 'dart:math';

import 'package:device_info_plus/device_info_plus.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../config/app_config.dart';
import 'hive_service.dart';

// ---------------------------------------------------------------------------
// AppInstallService
// ---------------------------------------------------------------------------

/// Registers the app installation on the backend exactly once per device.
///
/// On subsequent launches it silently updates `last_seen_at` and
/// `launch_count` via the same idempotent `POST /app/register-install`
/// endpoint.  All errors are swallowed — install tracking is non-critical.
class AppInstallService {
  static const _installIdKey = 'app_install_id';

  static final Dio _dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
      headers: const {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  /// Call once per app launch (fire-and-forget via `unawaited`).
  static Future<void> registerOrUpdate() async {
    try {
      final platform = _platform();
      if (platform == null) return; // web / desktop — skip

      // Retrieve or generate a stable install ID stored in Hive.
      String? installId =
          HiveService.settings.get(_installIdKey) as String?;
      if (installId == null) {
        installId = _generateUuid();
        await HiveService.settings.put(_installIdKey, installId);
      }

      final packageInfo = await PackageInfo.fromPlatform();
      final devicePlugin = DeviceInfoPlugin();

      String? deviceModel;
      String? osVersion;

      if (defaultTargetPlatform == TargetPlatform.android) {
        final android = await devicePlugin.androidInfo;
        deviceModel = '${android.manufacturer} ${android.model}'.trim();
        osVersion = 'Android ${android.version.release}';
      } else if (defaultTargetPlatform == TargetPlatform.iOS) {
        final ios = await devicePlugin.iosInfo;
        deviceModel = ios.model;
        osVersion = 'iOS ${ios.systemVersion}';
      }

      await _dio.post<void>('/app/register-install', data: {
        'install_id': installId,
        'platform': platform,
        'app_version': packageInfo.version,
        if (osVersion != null) 'os_version': osVersion,
        if (deviceModel != null) 'device_model': deviceModel,
      });
    } catch (_) {
      // Intentionally swallowed — install tracking must never break the app.
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

  /// Generates a RFC-4122 v4 UUID without external dependencies.
  static String _generateUuid() {
    final rng = Random.secure();
    final bytes = List<int>.generate(16, (_) => rng.nextInt(256));
    // Set version 4 bits (0100xxxx)
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    // Set variant bits (10xxxxxx)
    bytes[8] = (bytes[8] & 0x3f) | 0x80;

    final hex =
        bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
    return '${hex.substring(0, 8)}-'
        '${hex.substring(8, 12)}-'
        '${hex.substring(12, 16)}-'
        '${hex.substring(16, 20)}-'
        '${hex.substring(20)}';
  }
}
