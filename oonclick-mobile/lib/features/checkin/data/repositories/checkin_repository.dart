import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/checkin_model.dart';

/// Repository gérant les endpoints de check-in quotidien.
///
/// Toutes les méthodes lèvent [ApiException] en cas d'erreur.
class CheckinRepository {
  CheckinRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Statut du check-in
  // ---------------------------------------------------------------------------

  /// GET /checkin/status — retourne le statut du check-in de l'utilisateur.
  Future<CheckinStatusModel> getStatus() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/checkin/status');
      return CheckinStatusModel.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Effectuer le check-in
  // ---------------------------------------------------------------------------

  /// POST /checkin — effectue le check-in quotidien et retourne le résultat.
  ///
  /// Lance [ApiException] si l'utilisateur a déjà fait son check-in aujourd'hui.
  Future<CheckinResultModel> checkin() async {
    try {
      final response =
          await _api.post<Map<String, dynamic>>('/checkin');
      return CheckinResultModel.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final checkinRepositoryProvider = Provider<CheckinRepository>((ref) {
  return CheckinRepository(ref.read(apiClientProvider));
});
