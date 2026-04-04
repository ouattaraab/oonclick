import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_client.dart';
import 'fcm_service.dart';

/// Handles the backend-side lifecycle of FCM tokens: registration on login
/// and removal on logout.
///
/// The token itself is managed by [FcmService]; this service is only
/// responsible for syncing it with the Laravel API.
class FcmApiService {
  FcmApiService(this._api);

  final ApiClient _api;

  // -------------------------------------------------------------------------
  // POST /api/fcm/register
  // -------------------------------------------------------------------------

  /// Registers the current device FCM token with the authenticated backend.
  ///
  /// Should be called immediately after a successful login or OTP verification.
  /// Safe to call multiple times — the backend uses `updateOrCreate`.
  Future<void> registerToken() async {
    final token = await FcmService.getToken();
    if (token == null) {
      if (kDebugMode) debugPrint('[FCM API] No token available, skipping registration.');
      return;
    }

    final deviceType = Platform.isAndroid ? 'android' : 'ios';
    String? deviceName;

    try {
      // Best-effort: use the hostname as a human-readable device identifier.
      deviceName = Platform.localHostname;
    } catch (_) {
      deviceName = null;
    }

    try {
      await _api.post('/fcm/register', data: {
        'token':       token,
        'device_type': deviceType,
        'device_name': deviceName,
      });

      if (kDebugMode) debugPrint('[FCM API] Token registered ($deviceType).');
    } catch (e) {
      // Non-fatal — push simply won't work until next successful registration.
      if (kDebugMode) debugPrint('[FCM API] Token registration failed: $e');
    }
  }

  // -------------------------------------------------------------------------
  // POST /api/fcm/unregister
  // -------------------------------------------------------------------------

  /// Removes the current device FCM token from the backend.
  ///
  /// Should be called before or during logout so the server stops sending
  /// notifications to this device.
  Future<void> unregisterToken() async {
    final token = await FcmService.getToken();
    if (token == null) {
      if (kDebugMode) debugPrint('[FCM API] No token to unregister.');
      return;
    }

    try {
      await _api.post('/fcm/unregister', data: {'token': token});

      // Also delete the token locally so Firebase generates a new one on next
      // login, preventing stale token reuse.
      await FcmService.deleteToken();

      if (kDebugMode) debugPrint('[FCM API] Token unregistered.');
    } catch (e) {
      if (kDebugMode) debugPrint('[FCM API] Token unregistration failed: $e');
    }
  }
}

// ---------------------------------------------------------------------------
// Riverpod provider
// ---------------------------------------------------------------------------

final fcmApiServiceProvider = Provider<FcmApiService>((ref) {
  final api = ref.watch(apiClientProvider);
  return FcmApiService(api);
});
