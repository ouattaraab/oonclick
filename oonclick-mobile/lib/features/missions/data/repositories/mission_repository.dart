import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/mission_model.dart';

/// Repository gérant les endpoints de missions quotidiennes.
class MissionRepository {
  MissionRepository(this._api);

  final ApiClient _api;

  /// GET /missions — retourne les missions du jour pour l'utilisateur.
  Future<List<MissionModel>> getMissions() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/missions');
      final data = (response.data!['data'] as List<dynamic>?) ?? [];
      return data
          .map((e) => MissionModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// POST /missions/{id}/claim — réclame la récompense d'une mission complétée.
  Future<MissionClaimResult> claimReward(int id) async {
    try {
      final response =
          await _api.post<Map<String, dynamic>>('/missions/$id/claim');
      return MissionClaimResult.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final missionRepositoryProvider = Provider<MissionRepository>((ref) {
  return MissionRepository(ref.read(apiClientProvider));
});
