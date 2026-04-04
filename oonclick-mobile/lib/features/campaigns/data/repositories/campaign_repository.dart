import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../../../wallet/data/models/wallet_model.dart';
import '../models/campaign_model.dart';

/// Repository pour tous les appels API liés aux campagnes.
class CampaignRepository {
  CampaignRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Lister les campagnes (paginé)
  // ---------------------------------------------------------------------------

  /// GET /campaigns?page=[page]&status=[status]&format=[format]
  Future<PaginatedResult<CampaignModel>> getCampaigns({
    int page = 1,
    String? status,
    String? format,
  }) async {
    try {
      final params = <String, dynamic>{'page': page};
      if (status != null && status.isNotEmpty) params['status'] = status;
      if (format != null && format.isNotEmpty) params['format'] = format;

      final response = await _api.get<Map<String, dynamic>>(
        '/campaigns',
        params: params,
      );
      final data = response.data as Map<String, dynamic>;
      return PaginatedResult.fromJson(data, CampaignModel.fromJson);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Détail d'une campagne
  // ---------------------------------------------------------------------------

  /// GET /campaigns/{id}
  Future<CampaignDetailModel> getCampaign(int id) async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/campaigns/$id');
      return CampaignDetailModel.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Progression en temps réel d'une campagne
  // ---------------------------------------------------------------------------

  /// GET /campaigns/{id} — utilisé pour le polling temps réel.
  Future<CampaignDetailModel> getCampaignProgress(int id) async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/campaigns/$id');
      return CampaignDetailModel.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Créer une campagne
  // ---------------------------------------------------------------------------

  /// POST /campaigns
  Future<CampaignModel> createCampaign({
    required String title,
    String? description,
    required String format,
    required int budget,
    required int costPerView,
    Map<String, dynamic>? targeting,
    int? durationSeconds,
    String? endMode,
  }) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/campaigns',
        data: {
          'title': title,
          if (description != null && description.isNotEmpty)
            'description': description,
          'format': format,
          'budget': budget,
          'cost_per_view': costPerView,
          'targeting': targeting,
          'duration_seconds': durationSeconds,
          if (endMode != null) 'end_mode': endMode,
        },
      );
      final data = response.data as Map<String, dynamic>;
      final campaignData =
          data['campaign'] as Map<String, dynamic>? ??
          data['data'] as Map<String, dynamic>? ??
          data;
      return CampaignModel.fromJson(campaignData);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Modifier une campagne (brouillon uniquement)
  // ---------------------------------------------------------------------------

  /// PATCH /campaigns/{id}
  Future<CampaignModel> updateCampaign(
    int id, {
    String? title,
    String? description,
    String? format,
    int? budget,
    int? costPerView,
    Map<String, dynamic>? targeting,
    int? durationSeconds,
    String? endMode,
  }) async {
    try {
      final body = <String, dynamic>{};
      if (title != null) body['title'] = title;
      if (description != null) body['description'] = description;
      if (format != null) body['format'] = format;
      if (budget != null) body['budget'] = budget;
      if (costPerView != null) body['cost_per_view'] = costPerView;
      if (targeting != null) body['targeting'] = targeting;
      if (durationSeconds != null) body['duration_seconds'] = durationSeconds;
      if (endMode != null) body['end_mode'] = endMode;

      final response = await _api.patch<Map<String, dynamic>>(
        '/campaigns/$id',
        data: body,
      );
      final data = response.data as Map<String, dynamic>;
      final campaignData =
          data['campaign'] as Map<String, dynamic>? ??
          data['data'] as Map<String, dynamic>? ??
          data;
      return CampaignModel.fromJson(campaignData);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Supprimer une campagne (brouillon uniquement)
  // ---------------------------------------------------------------------------

  /// DELETE /campaigns/{id}
  Future<void> deleteCampaign(int id) async {
    try {
      await _api.delete('/campaigns/$id');
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Upload du média
  // ---------------------------------------------------------------------------

  /// POST /campaigns/{id}/media — multipart upload
  Future<CampaignModel> uploadMedia(
    int id, {
    required String mediaFilePath,
    String? thumbnailFilePath,
  }) async {
    try {
      final fields = <String, dynamic>{
        'media': await MultipartFile.fromFile(mediaFilePath),
      };
      if (thumbnailFilePath != null) {
        fields['thumbnail'] = await MultipartFile.fromFile(thumbnailFilePath);
      }
      final response = await _api.postFormData<Map<String, dynamic>>(
        '/campaigns/$id/media',
        FormData.fromMap(fields),
      );
      final data = response.data as Map<String, dynamic>;
      final campaignData =
          data['campaign'] as Map<String, dynamic>? ??
          data['data'] as Map<String, dynamic>? ??
          data;
      return CampaignModel.fromJson(campaignData);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Actions de statut
  // ---------------------------------------------------------------------------

  /// POST /campaigns/{id}/submit — soumet pour révision
  Future<CampaignModel> submitCampaign(int id) async {
    return _statusAction(id, 'submit');
  }

  /// POST /campaigns/{id}/pause — met en pause
  Future<CampaignModel> pauseCampaign(int id) async {
    return _statusAction(id, 'pause');
  }

  /// POST /campaigns/{id}/resume — reprend une campagne en pause
  Future<CampaignModel> resumeCampaign(int id) async {
    return _statusAction(id, 'resume');
  }

  /// POST /campaigns/{id}/duplicate — duplique en brouillon
  Future<CampaignModel> duplicateCampaign(int id) async {
    return _statusAction(id, 'duplicate');
  }

  Future<CampaignModel> _statusAction(int id, String action) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/campaigns/$id/$action',
      );
      final data = response.data as Map<String, dynamic>;
      final campaignData =
          data['campaign'] as Map<String, dynamic>? ??
          data['data'] as Map<String, dynamic>? ??
          data;
      return CampaignModel.fromJson(campaignData);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final campaignRepositoryProvider = Provider<CampaignRepository>((ref) {
  return CampaignRepository(ref.read(apiClientProvider));
});
