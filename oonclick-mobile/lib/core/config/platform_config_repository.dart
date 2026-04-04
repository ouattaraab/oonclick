import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import 'feature_config_model.dart';
import 'platform_config_model.dart';

/// Repository qui récupère la configuration de la plateforme depuis l'API.
///
/// Endpoints consommés :
/// - GET /config/campaign-formats
/// - GET /config/audience-criteria
/// - GET /config/features
class PlatformConfigRepository {
  PlatformConfigRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Formats de campagne
  // ---------------------------------------------------------------------------

  /// Récupère la liste des formats publicitaires actifs.
  Future<List<CampaignFormatConfig>> getCampaignFormats() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/config/campaign-formats');
      final body = response.data as Map<String, dynamic>;
      final data = body['data'] as List;
      return data
          .map((e) => CampaignFormatConfig.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Critères d'audience
  // ---------------------------------------------------------------------------

  /// Récupère la liste des critères d'audience configurés.
  Future<List<AudienceCriterionConfig>> getAudienceCriteria() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/config/audience-criteria');
      final body = response.data as Map<String, dynamic>;
      final data = body['data'] as List;
      return data
          .map((e) =>
              AudienceCriterionConfig.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Fonctionnalités dynamiques
  // ---------------------------------------------------------------------------

  /// Récupère la liste des fonctionnalités activées sur la plateforme.
  Future<List<FeatureConfig>> getEnabledFeatures() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/config/features');
      final data =
          (response.data as Map<String, dynamic>)['data'] as List<dynamic>;
      return data
          .map((e) => FeatureConfig.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final platformConfigRepositoryProvider =
    Provider<PlatformConfigRepository>((ref) {
  return PlatformConfigRepository(ref.read(apiClientProvider));
});
