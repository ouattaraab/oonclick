import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/kyc_model.dart';

/// Repository gérant les endpoints de vérification d'identité (KYC).
class KycRepository {
  KycRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Statut KYC
  // ---------------------------------------------------------------------------

  /// GET /kyc/status — retourne le niveau KYC actuel et le statut par niveau.
  Future<KycStatusModel> getStatus() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/kyc/status');
      return KycStatusModel.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Documents soumis
  // ---------------------------------------------------------------------------

  /// GET /kyc/documents — retourne la liste des documents déjà soumis.
  Future<List<KycDocumentModel>> getDocuments() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/kyc/documents');
      final data = response.data as Map<String, dynamic>;
      final list = data['data'] as List<dynamic>? ??
          data['documents'] as List<dynamic>? ??
          [];
      return list
          .whereType<Map<String, dynamic>>()
          .map(KycDocumentModel.fromJson)
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Soumettre un document
  // ---------------------------------------------------------------------------

  /// POST /kyc/submit — soumet un fichier de document KYC en multipart.
  ///
  /// [level] : niveau KYC concerné (1, 2, ou 3).
  /// [type]  : type de document (ex: 'national_id', 'selfie').
  /// [file]  : fichier image ou PDF à envoyer.
  Future<void> submitDocument({
    required int level,
    required String type,
    required File file,
  }) async {
    try {
      final formData = FormData.fromMap({
        'level': level,
        'document_type': type,
        'file': await MultipartFile.fromFile(
          file.path,
          filename: file.path.split('/').last,
        ),
      });
      await _api.postFormData('/kyc/submit', formData);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final kycRepositoryProvider = Provider<KycRepository>((ref) {
  return KycRepository(ref.read(apiClientProvider));
});
