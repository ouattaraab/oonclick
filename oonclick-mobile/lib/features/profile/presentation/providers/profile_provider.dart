import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_exception.dart';
import '../../../auth/data/models/user_model.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/models/profile_stats_model.dart';
import '../../data/models/referral_tree_model.dart';
import '../../data/repositories/profile_repository.dart';

// ---------------------------------------------------------------------------
// Profile notifier
// ---------------------------------------------------------------------------

/// Gère l'état du profil de l'utilisateur connecté.
///
/// - [build] initialise depuis [currentUserProvider] (données en cache).
/// - [refresh] rafraîchit depuis l'API et synchronise [authProvider].
/// - [logout] délègue à [AuthNotifier.logout].
class ProfileNotifier extends AsyncNotifier<UserModel?> {
  @override
  Future<UserModel?> build() async {
    // Initialiser depuis le cache synchrone de authProvider.
    return ref.watch(currentUserProvider);
  }

  // ---------------------------------------------------------------------------
  // Rafraîchir le profil
  // ---------------------------------------------------------------------------

  /// Appelle GET /auth/me et met à jour l'état local ainsi que authProvider.
  Future<void> refresh() async {
    state = const AsyncLoading();
    try {
      final repo = ref.read(profileRepositoryProvider);
      final user = await repo.getMe();

      // Mettre à jour authProvider pour garder la source de vérité cohérente.
      final authNotifier = ref.read(authProvider.notifier);
      authNotifier.updateUserLocally(user);

      state = AsyncData(user);
    } on ApiException catch (e) {
      state = AsyncError(e, StackTrace.current);
    }
  }

  // ---------------------------------------------------------------------------
  // Déconnexion
  // ---------------------------------------------------------------------------

  /// Délègue la déconnexion à [AuthNotifier.logout].
  Future<void> logout() async {
    await ref.read(authProvider.notifier).logout();
  }
}

/// Provider principal du profil.
final profileProvider =
    AsyncNotifierProvider<ProfileNotifier, UserModel?>(ProfileNotifier.new);

// ---------------------------------------------------------------------------
// Stats provider
// ---------------------------------------------------------------------------

/// Charge les statistiques enrichies du profil.
///
/// Dépend de [profileProvider] pour se réinitialiser lorsque l'utilisateur change.
final profileStatsProvider = FutureProvider<ProfileStatsModel>((ref) async {
  // Observer le profil pour invalider les stats si l'utilisateur change.
  ref.watch(profileProvider);

  final repo = ref.read(profileRepositoryProvider);
  return repo.getStats();
});

// ---------------------------------------------------------------------------
// Arbre de parrainage
// ---------------------------------------------------------------------------

/// Charge l'arbre de parrainage à 2 niveaux depuis GET /referrals/tree.
///
/// Se réinitialise automatiquement lorsque le profil change.
final referralTreeProvider = FutureProvider<ReferralTreeModel>((ref) async {
  ref.watch(profileProvider);
  final repo = ref.read(profileRepositoryProvider);
  return repo.getReferralTree();
});
