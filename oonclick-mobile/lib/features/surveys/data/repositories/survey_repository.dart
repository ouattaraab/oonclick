import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/survey_model.dart';

/// Repository gérant les endpoints de sondages rémunérés.
class SurveyRepository {
  SurveyRepository(this._api);

  final ApiClient _api;

  /// GET /surveys — liste des sondages disponibles pour l'utilisateur.
  Future<List<SurveyModel>> getSurveys() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/surveys');
      final data = (response.data!['data'] as List<dynamic>?) ?? [];
      return data
          .map((e) => SurveyModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// GET /surveys/{id} — détail d'un sondage avec ses questions.
  Future<SurveyModel> getSurvey(int id) async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/surveys/$id');
      return SurveyModel.fromJson(
          response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// POST /surveys/{id}/submit — soumet les réponses et réclame la récompense.
  ///
  /// [answers] est une liste indexée par position de question.
  /// Chaque élément peut être une String (text/radio) ou une liste de String (checkbox).
  Future<SurveySubmitResult> submitSurvey(
      int id, List<dynamic> answers) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/surveys/$id/submit',
        data: {'answers': answers},
      );
      return SurveySubmitResult.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final surveyRepositoryProvider = Provider<SurveyRepository>((ref) {
  return SurveyRepository(ref.read(apiClientProvider));
});
