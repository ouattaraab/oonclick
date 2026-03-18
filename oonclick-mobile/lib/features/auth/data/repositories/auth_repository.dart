import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/auth_response_model.dart';
import '../models/user_model.dart';

/// Repository handling all authentication endpoints.
///
/// All methods throw [ApiException] on failure — callers are responsible for
/// catching and surfacing errors to the UI layer.
class AuthRepository {
  AuthRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Registration flow
  // ---------------------------------------------------------------------------

  /// Step 1 — registers a new account.
  /// POST /auth/register
  ///
  /// On success the API sends an OTP to [phone].
  Future<void> register(String phone, String role) async {
    try {
      await _api.post('/auth/register', data: {
        'phone': phone,
        'role': role,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // OTP verification
  // ---------------------------------------------------------------------------

  /// Step 2 — verifies the OTP and returns [AuthResponseModel] with the token.
  /// POST /auth/verify-otp
  ///
  /// [type] is `register` or `login`.
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
      return AuthResponseModel.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // OTP resend
  // ---------------------------------------------------------------------------

  /// POST /auth/resend-otp
  Future<void> resendOtp(String phone, String type) async {
    try {
      await _api.post('/auth/resend-otp', data: {
        'phone': phone,
        'type': type,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Login (existing accounts)
  // ---------------------------------------------------------------------------

  /// POST /auth/login — sends an OTP to an existing phone number.
  Future<void> login(String phone) async {
    try {
      await _api.post('/auth/login', data: {'phone': phone});
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Logout
  // ---------------------------------------------------------------------------

  /// POST /auth/logout — invalidates the current Sanctum token server-side.
  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } on DioException catch (e) {
      // Swallow 401 on logout — the token may already be invalid.
      final apiEx = ApiException.fromDioError(e);
      if (!apiEx.isUnauthorized) rethrow;
    }
  }

  // ---------------------------------------------------------------------------
  // Current user
  // ---------------------------------------------------------------------------

  /// GET /auth/me — returns the profile of the currently authenticated user.
  Future<UserModel> me() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/auth/me');
      final data = response.data as Map<String, dynamic>;
      final userJson =
          data['data'] as Map<String, dynamic>? ?? data;
      return UserModel.fromJson(userJson);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Profile completion (subscribers)
  // ---------------------------------------------------------------------------

  /// POST /auth/complete-profile — submits multi-step subscriber profile data.
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
  return AuthRepository(ref.read(apiClientProvider));
});
