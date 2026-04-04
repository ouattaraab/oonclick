import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../../../auth/data/models/user_model.dart';
import '../models/profile_stats_model.dart';
import '../models/referral_tree_model.dart';

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
      // Backend returns { "user": {...}, "profile": {...}, "wallet": {...} }
      final userJson = body['user'] as Map<String, dynamic>?
          ?? body['data'] as Map<String, dynamic>?
          ?? body;
      return UserModel.fromJson(userJson);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Mettre à jour le profil
  // ---------------------------------------------------------------------------

  /// GET /auth/me — retourne les données brutes du profil subscriber.
  Future<Map<String, dynamic>> getProfileData() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/auth/me');
      final body = response.data as Map<String, dynamic>;
      return body['profile'] as Map<String, dynamic>? ?? {};
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// PATCH /auth/profile — met à jour les champs modifiables du profil.
  ///
  /// [data] peut contenir : `first_name`, `last_name`, `city`, `operator`,
  /// `interests`, `custom_fields` (Map des critères d'audience dynamiques).
  Future<void> updateProfile(Map<String, dynamic> data) async {
    try {
      await _api.patch('/auth/profile', data: data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Upload avatar
  // ---------------------------------------------------------------------------

  /// POST /auth/avatar — envoie une image en multipart form-data.
  ///
  /// [file] : fichier image (JPEG, PNG, WebP recommandé).
  Future<void> uploadAvatar(File file) async {
    try {
      final formData = FormData.fromMap({
        'avatar': await MultipartFile.fromFile(
          file.path,
          filename: file.path.split('/').last,
        ),
      });
      await _api.postFormData('/auth/avatar', formData);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Gestion des consentements
  // ---------------------------------------------------------------------------

  /// GET /consents — retourne tous les consentements de l'utilisateur connecté.
  Future<List<Map<String, dynamic>>> getConsents() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/consents');
      final body = response.data as Map<String, dynamic>;
      final list = body['data'] as List<dynamic>? ?? [];
      return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// POST /consents — met à jour un consentement individuel (C5 ou C6).
  ///
  /// [type] : 'C5' ou 'C6' (C1–C4 sont verrouillés côté serveur).
  /// [granted] : true pour accorder, false pour révoquer.
  Future<void> updateConsent(String type, {required bool granted}) async {
    try {
      await _api.post('/consents', data: {
        'consent_type': type,
        'granted': granted,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Export de données
  // ---------------------------------------------------------------------------

  /// GET /auth/export-data — retourne toutes les données de l'utilisateur.
  Future<Map<String, dynamic>> exportData() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/auth/export-data');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Suppression de compte
  // ---------------------------------------------------------------------------

  /// DELETE /auth/delete-account — supprime définitivement le compte.
  Future<void> deleteAccount() async {
    try {
      await _api.delete('/auth/delete-account');
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Arbre de parrainage
  // ---------------------------------------------------------------------------

  /// GET /referrals/tree — retourne l'arbre de parrainage à 2 niveaux.
  ///
  /// Inclut les filleuls directs (niveau 1), les filleuls des filleuls (niveau 2),
  /// les gains cumulés par niveau et la configuration de la feature.
  Future<ReferralTreeModel> getReferralTree() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/referrals/tree');
      final body = response.data as Map<String, dynamic>;
      return ReferralTreeModel.fromJson(body);
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
  return ProfileRepository(ref.watch(apiClientProvider));
});
