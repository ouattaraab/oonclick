import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/app_config.dart';
import 'api_exception.dart';

// ---------------------------------------------------------------------------
// Auth token provider
// ---------------------------------------------------------------------------

/// Holds the current Bearer token in memory.
/// Populated by the auth feature after login / restored from secure storage.
final authTokenProvider = StateProvider<String?>((ref) => null);

// ---------------------------------------------------------------------------
// ApiClient
// ---------------------------------------------------------------------------

/// Dio-based HTTP client pre-configured for the oon.click Laravel API.
///
/// Features:
/// - Automatic `Authorization: Bearer <token>` injection.
/// - All errors are mapped to [ApiException].
/// - Debug-only request/response logging (stripped from release builds).
class ApiClient {
  ApiClient(String? token) {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: const Duration(seconds: AppConfig.connectTimeoutSeconds),
        receiveTimeout: const Duration(seconds: AppConfig.receiveTimeoutSeconds),
        headers: const {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    // ---- Auth interceptor ----
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          // Map every Dio error to ApiException before it reaches callers.
          final apiException = ApiException.fromDioError(error);
          handler.next(
            DioException(
              requestOptions: error.requestOptions,
              response: error.response,
              error: apiException,
              type: error.type,
            ),
          );
        },
      ),
    );

    // ---- Debug logger (excluded from release builds via assert) ----
    assert(() {
      _dio.interceptors.add(
        LogInterceptor(
          requestBody: true,
          responseBody: true,
          requestHeader: true,
          responseHeader: false,
          error: true,
          logPrint: (obj) => debugPrint('[ApiClient] ${obj.toString()}'),
        ),
      );
      return true;
    }());
  }

  late final Dio _dio;

  // ---------------------------------------------------------------------------
  // Public HTTP methods
  // ---------------------------------------------------------------------------

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? params,
  }) =>
      _dio.get<T>(path, queryParameters: params);

  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
  }) =>
      _dio.post<T>(path, data: data);

  Future<Response<T>> patch<T>(
    String path, {
    dynamic data,
  }) =>
      _dio.patch<T>(path, data: data);

  Future<Response<T>> put<T>(
    String path, {
    dynamic data,
  }) =>
      _dio.put<T>(path, data: data);

  Future<Response<T>> delete<T>(String path) => _dio.delete<T>(path);

  /// Sends a multipart form-data request (file uploads).
  Future<Response<T>> postFormData<T>(String path, FormData data) =>
      _dio.post<T>(path, data: data);

  /// Sends a multipart form-data PATCH request (file updates).
  Future<Response<T>> patchFormData<T>(String path, FormData data) =>
      _dio.patch<T>(path, data: data);
}

// ---------------------------------------------------------------------------
// Riverpod provider
// ---------------------------------------------------------------------------

/// Provides an [ApiClient] scoped to the current auth token.
///
/// The client is automatically recreated whenever the token changes,
/// ensuring the `Authorization` header is always up to date.
final apiClientProvider = Provider<ApiClient>((ref) {
  final token = ref.watch(authTokenProvider);
  return ApiClient(token);
});
