import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../../../auth/data/models/user_model.dart';
import '../models/profile_stats_model.dart';

/// Repository gérant les endpoints liés au profil utilisateur.
///
/// Toutes les méthodes lèvent [ApiException] en cas d'erreur réseau ou serveur.
class ProfileRepository {
  ProfileRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Récupérer le profil courant
  // ---------------------------------------------------------------------------

  /// GET /auth/me — retourne l'utilisateur avec son profil et son wallet.
  Future<UserModel> getMe() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/auth/me');
      final body = response.data as Map<String, dynamic>;
      final userJson = body['data'] as Map<String, dynamic>? ?? body;
      return UserModel.fromJson(userJson);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Mettre à jour le profil
  // ---------------------------------------------------------------------------

  /// PATCH /auth/complete-profile — met à jour les champs modifiables du profil.
  ///
  /// [data] peut contenir : `city`, `operator`, `interests` (liste de chaînes),
  /// `name`, `email`, etc.
  Future<void> updateProfile(Map<String, dynamic> data) async {
    try {
      await _api.patch('/auth/complete-profile', data: data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Statistiques du profil
  // ---------------------------------------------------------------------------

  /// GET /auth/me — extrait les statistiques depuis la réponse enrichie.
  ///
  /// Le serveur retourne dans `data` : wallet (balance, total_earned,
  /// total_withdrawn), trust_score, kyc_level, referral_code, total_views…
  Future<ProfileStatsModel> getStats() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/auth/me');
      final body = response.data as Map<String, dynamic>;
      final data = body['data'] as Map<String, dynamic>? ?? body;
      return ProfileStatsModel.fromJson(data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final profileRepositoryProvider = Provider<ProfileRepository>((ref) {
  return ProfileRepository(ref.read(apiClientProvider));
});
