import 'package:dio/dio.dart';

/// Unified exception type for all API errors.
///
/// Wraps Dio errors and parses the Laravel JSON error format:
/// ```json
/// { "message": "...", "errors": { "field": ["msg"] } }
/// ```
class ApiException implements Exception {
  const ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  final String message;

  /// HTTP status code, if available.
  final int? statusCode;

  /// Validation errors returned by Laravel on HTTP 422.
  /// Key = field name, value = list of error messages.
  final Map<String, dynamic>? errors;

  // ---------------------------------------------------------------------------
  // Factory constructors
  // ---------------------------------------------------------------------------

  /// Builds an [ApiException] from a [DioException].
  factory ApiException.fromDioError(DioException error) {
    final response = error.response;

    if (response == null) {
      // Network / timeout errors — no HTTP response available.
      return switch (error.type) {
        DioExceptionType.connectionTimeout ||
        DioExceptionType.sendTimeout ||
        DioExceptionType.receiveTimeout =>
          const ApiException(
            message: 'La requête a expiré. Vérifiez votre connexion internet.',
            statusCode: null,
          ),
        DioExceptionType.connectionError =>
          const ApiException(
            message:
                'Impossible de joindre le serveur. Vérifiez votre connexion internet.',
            statusCode: null,
          ),
        _ => ApiException(
            message: error.message ?? 'Une erreur inattendue est survenue.',
            statusCode: null,
          ),
      };
    }

    final statusCode = response.statusCode;
    final data = response.data;

    // Attempt to extract the Laravel `message` field.
    String serverMessage = _extractMessage(data, statusCode);

    // Extract validation errors (HTTP 422).
    Map<String, dynamic>? validationErrors;
    if (statusCode == 422 && data is Map<String, dynamic>) {
      final raw = data['errors'];
      if (raw is Map<String, dynamic>) {
        validationErrors = raw;
      }
    }

    return ApiException(
      message: serverMessage,
      statusCode: statusCode,
      errors: validationErrors,
    );
  }

  /// Convenience constructor for locally generated errors (e.g. missing token).
  factory ApiException.local(String message) =>
      ApiException(message: message);

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  static String _extractMessage(dynamic data, int? statusCode) {
    if (data is Map<String, dynamic>) {
      final msg = data['message'];
      if (msg is String && msg.isNotEmpty) return msg;
    }

    return switch (statusCode) {
      400 => 'Requête invalide.',
      401 => 'Vous n\'êtes pas authentifié. Veuillez vous reconnecter.',
      403 => 'Accès refusé. Vous n\'avez pas les permissions nécessaires.',
      404 => 'La ressource demandée est introuvable.',
      408 => 'Le serveur n\'a pas répondu à temps.',
      422 => 'Les données envoyées sont invalides.',
      429 => 'Trop de requêtes. Veuillez patienter avant de réessayer.',
      500 => 'Erreur interne du serveur. Veuillez réessayer plus tard.',
      502 || 503 || 504 => 'Le service est temporairement indisponible.',
      _ => 'Une erreur inattendue est survenue (code $statusCode).',
    };
  }

  // ---------------------------------------------------------------------------
  // Convenience getters
  // ---------------------------------------------------------------------------

  bool get isValidationError => statusCode == 422;
  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isNotFound => statusCode == 404;
  bool get isServerError => statusCode != null && statusCode! >= 500;
  bool get isNetworkError => statusCode == null;

  /// Returns the first validation error message for [field], or null.
  String? fieldError(String field) {
    final fieldErrors = errors?[field];
    if (fieldErrors is List && fieldErrors.isNotEmpty) {
      return fieldErrors.first.toString();
    }
    return null;
  }

  @override
  String toString() => 'ApiException($statusCode): $message';
}
