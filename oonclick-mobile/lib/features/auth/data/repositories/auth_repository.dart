import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../../../../core/services/fcm_api_service.dart';
import '../models/auth_response_model.dart';
import '../models/user_model.dart';

/// Repository handling all authentication endpoints.
///
/// Supports registration/login via phone (Firebase Phone Auth) or email (backend OTP).
class AuthRepository {
  AuthRepository(this._api, this._fcmApi);

  final ApiClient _api;
  final FcmApiService _fcmApi;

  // ---------------------------------------------------------------------------
  // Registration flow
  // ---------------------------------------------------------------------------

  /// Step 1 — registers a new account via phone or email.
  /// POST /auth/register
  ///
  /// [method] is `phone` or `email`.
  /// [identifier] is either the phone number or email address.
  /// [consents] optional map of granular consent flags (C1–C6).
  Future<void> register(
    String identifier,
    String role, {
    String method = 'phone',
    Map<String, dynamic>? consents,
  }) async {
    try {
      final data = <String, dynamic>{
        'role': role,
        'method': method,
      };

      if (method == 'phone') {
        data['phone'] = identifier;
      } else {
        data['email'] = identifier;
      }

      if (consents != null) {
        data.addAll(consents);
      }

      await _api.post('/auth/register', data: data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // OTP verification (email-based)
  // ---------------------------------------------------------------------------

  /// Step 2 — verifies the OTP (email) and returns [AuthResponseModel].
  /// POST /auth/verify-otp
  Future<AuthResponseModel> verifyOtp({
    required String phone,
    required String code,
    required String type,
    String? fingerprint,
    String? platform,
  }) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/auth/verify-otp',
        data: {
          'phone': phone,
          'code': code,
          'type': type,
          'fingerprint': fingerprint,
          'platform': platform,
        },
      );

      final authResponse = AuthResponseModel.fromJson(
        response.data as Map<String, dynamic>,
      );

      _fcmApi.registerToken().ignore();
      return authResponse;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Firebase Phone Auth verification
  // ---------------------------------------------------------------------------

  /// Verifies a Firebase ID token with the backend.
  /// POST /auth/verify-firebase
  ///
  /// After Firebase verifies the phone number, this sends the Firebase ID token
  /// to the backend which validates it and returns a Sanctum token.
  Future<AuthResponseModel> verifyWithFirebase({
    required String phone,
    required String firebaseIdToken,
    required String type,
    String? fingerprint,
    String? platform,
  }) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/auth/verify-firebase',
        data: {
          'phone': phone,
          'firebase_id_token': firebaseIdToken,
          'type': type,
          'fingerprint': fingerprint,
          'platform': platform,
        },
      );

      final authResponse = AuthResponseModel.fromJson(
        response.data as Map<String, dynamic>,
      );

      _fcmApi.registerToken().ignore();
      return authResponse;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Google Auth
  // ---------------------------------------------------------------------------

  /// Authenticates with a Firebase ID token from Google Sign-In.
  /// POST /auth/google
  ///
  /// The backend will create the user if they don't exist, or log them in
  /// if they already have an account.
  Future<AuthResponseModel> loginWithGoogle({
    required String firebaseIdToken,
    required String email,
    String? name,
    String role = 'subscriber',
    String? fingerprint,
    String? platform,
  }) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/auth/google',
        data: {
          'firebase_id_token': firebaseIdToken,
          'email': email,
          'name': name,
          'role': role,
          'fingerprint': fingerprint,
          'platform': platform,
        },
      );

      final authResponse = AuthResponseModel.fromJson(
        response.data as Map<String, dynamic>,
      );

      _fcmApi.registerToken().ignore();
      return authResponse;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // OTP resend
  // ---------------------------------------------------------------------------

  /// POST /auth/resend-otp
  Future<void> resendOtp(String identifier, String type) async {
    try {
      await _api.post('/auth/resend-otp', data: {
        'phone': identifier,
        'type': type,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Login (existing accounts)
  // ---------------------------------------------------------------------------

  /// POST /auth/login — sends an OTP to an existing phone/email.
  Future<void> login(String identifier, {String method = 'phone'}) async {
    try {
      final data = <String, dynamic>{'method': method};
      if (method == 'phone') {
        data['phone'] = identifier;
      } else {
        data['email'] = identifier;
      }
      await _api.post('/auth/login', data: data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Logout
  // ---------------------------------------------------------------------------

  /// POST /auth/logout — invalidates the current Sanctum token server-side.
  Future<void> logout() async {
    await _fcmApi.unregisterToken();

    try {
      await _api.post('/auth/logout');
    } on DioException catch (e) {
      final apiEx = ApiException.fromDioError(e);
      if (!apiEx.isUnauthorized) rethrow;
    }
  }

  // ---------------------------------------------------------------------------
  // Current user
  // ---------------------------------------------------------------------------

  /// GET /auth/me
  Future<UserModel> me() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/auth/me');
      final body = response.data as Map<String, dynamic>;
      final userJson = body['user'] as Map<String, dynamic>?
          ?? body['data'] as Map<String, dynamic>?
          ?? body;
      return UserModel.fromJson(userJson);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Profile completion (subscribers)
  // ---------------------------------------------------------------------------

  /// POST /auth/complete-profile
  Future<void> completeProfile(Map<String, dynamic> data) async {
    try {
      await _api.post('/auth/complete-profile', data: data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
    ref.watch(apiClientProvider),
    ref.watch(fcmApiServiceProvider),
  );
});
