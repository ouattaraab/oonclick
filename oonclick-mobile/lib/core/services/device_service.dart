import 'dart:convert';
import 'dart:io';

import 'package:crypto/crypto.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'secure_storage_service.dart';

// ---------------------------------------------------------------------------
// DeviceService
// ---------------------------------------------------------------------------

/// Provides device-level information used for session fingerprinting and
/// analytics.
///
/// The fingerprint is a SHA-256 hash of stable hardware identifiers and is
/// persisted in secure storage so it is computed only once per install.
class DeviceService {
  const DeviceService();

  // ---------------------------------------------------------------------------
  // Fingerprint
  // ---------------------------------------------------------------------------

  /// Returns a stable, privacy-preserving device fingerprint.
  ///
  /// The raw value is derived from stable OS identifiers and then hashed with
  /// SHA-256 so that no personal information is stored in plaintext.
  ///
  /// Pass a [cachedFingerprint] to skip recomputing (preferred — call
  /// [getOrCreateFingerprint] instead for the cached path).
  Future<String> computeFingerprint() async {
    final deviceInfo = DeviceInfoPlugin();
    String raw;

    if (Platform.isAndroid) {
      final info = await deviceInfo.androidInfo;
      // `id` is the Android Build.ID (changes with OS updates but is stable
      // within a version).  Combining with model + brand gives good entropy.
      raw = '${info.id}_${info.model}_${info.brand}';
    } else if (Platform.isIOS) {
      final info = await deviceInfo.iosInfo;
      // `identifierForVendor` is reset only on full reinstall or factory reset.
      raw = '${info.identifierForVendor}_${info.model}';
    } else {
      // Fallback for any other platform (e.g. macOS/desktop during dev).
      raw = 'unknown_${DateTime.now().millisecondsSinceEpoch}';
    }

    final bytes = utf8.encode(raw);
    final digest = sha256.convert(bytes);
    return digest.toString();
  }

  // ---------------------------------------------------------------------------
  // Platform helpers
  // ---------------------------------------------------------------------------

  /// Returns a lowercase platform identifier: `android`, `ios`, or `web`.
  Future<String> getPlatform() async {
    if (Platform.isAndroid) return 'android';
    if (Platform.isIOS) return 'ios';
    return 'web';
  }

  /// Returns the device model string (e.g. "Pixel 8 Pro", "iPhone 15 Pro").
  Future<String?> getModel() async {
    final deviceInfo = DeviceInfoPlugin();

    if (Platform.isAndroid) {
      final info = await deviceInfo.androidInfo;
      return info.model;
    } else if (Platform.isIOS) {
      final info = await deviceInfo.iosInfo;
      return info.model;
    }

    return null;
  }

  /// Returns the OS version string (e.g. "15.0", "34").
  Future<String?> getOsVersion() async {
    final deviceInfo = DeviceInfoPlugin();

    if (Platform.isAndroid) {
      final info = await deviceInfo.androidInfo;
      return info.version.release;
    } else if (Platform.isIOS) {
      final info = await deviceInfo.iosInfo;
      return info.systemVersion;
    }

    return null;
  }

  /// Returns a map with all device metadata useful for the `/auth/register`
  /// and `/auth/login` API payloads.
  Future<Map<String, String?>> getDevicePayload({
    required String fingerprint,
  }) async {
    return {
      'device_fingerprint': fingerprint,
      'platform': await getPlatform(),
      'device_model': await getModel(),
      'os_version': await getOsVersion(),
    };
  }
}

// ---------------------------------------------------------------------------
// Riverpod providers
// ---------------------------------------------------------------------------

/// Global provider for [DeviceService].
final deviceServiceProvider = Provider<DeviceService>((ref) {
  return const DeviceService();
});

/// Async provider that computes (or restores from cache) the device fingerprint.
///
/// The value is persisted in [SecureStorageService] so it survives hot
/// restarts without recomputing.
final deviceFingerprintProvider = FutureProvider<String>((ref) async {
  final storage = ref.read(secureStorageProvider);

  // Return cached value if available.
  final cached = await storage.getFingerprint();
  if (cached != null && cached.isNotEmpty) return cached;

  // Compute, persist, then return.
  final service = ref.read(deviceServiceProvider);
  final fingerprint = await service.computeFingerprint();
  await storage.saveFingerprint(fingerprint);
  return fingerprint;
});
