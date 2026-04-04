import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/gamification_model.dart';

/// Repository gérant les endpoints de gamification.
class GamificationRepository {
  GamificationRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Profil de gamification
  // ---------------------------------------------------------------------------

  /// GET /gamification/profile — retourne le profil XP/niveaux/badges de l'utilisateur.
  Future<GamificationProfile> getProfile() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/gamification/profile');
      return GamificationProfile.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Badges
  // ---------------------------------------------------------------------------

  /// GET /gamification/badges — retourne tous les badges (gagnés et non gagnés).
  Future<List<BadgeModel>> getBadges() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/gamification/badges');
      final data = response.data as Map<String, dynamic>;
      final list = data['data'] as List<dynamic>? ??
          data['badges'] as List<dynamic>? ??
          [];
      return list
          .whereType<Map<String, dynamic>>()
          .map(BadgeModel.fromJson)
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Leaderboard
  // ---------------------------------------------------------------------------

  /// GET /gamification/leaderboard — retourne le classement des utilisateurs.
  Future<List<LeaderboardEntry>> getLeaderboard() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/gamification/leaderboard');
      final data = response.data as Map<String, dynamic>;
      final list = data['data'] as List<dynamic>? ??
          data['leaderboard'] as List<dynamic>? ??
          [];
      return list
          .whereType<Map<String, dynamic>>()
          .map(LeaderboardEntry.fromJson)
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final gamificationRepositoryProvider =
    Provider<GamificationRepository>((ref) {
  return GamificationRepository(ref.read(apiClientProvider));
});
