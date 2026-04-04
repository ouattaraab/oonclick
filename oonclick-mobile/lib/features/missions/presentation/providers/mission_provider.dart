import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/mission_model.dart';
import '../../data/repositories/mission_repository.dart';

// ---------------------------------------------------------------------------
// Missions notifier
// ---------------------------------------------------------------------------

/// Charge et gère les missions quotidiennes de l'utilisateur.
///
/// Expose :
/// - [state] : liste des missions du jour
/// - [refresh()] : recharge les missions
/// - [claimReward(id)] : réclame la récompense d'une mission et rafraîchit
final missionsProvider =
    AsyncNotifierProvider<MissionsNotifier, List<MissionModel>>(
        MissionsNotifier.new);

class MissionsNotifier extends AsyncNotifier<List<MissionModel>> {
  @override
  Future<List<MissionModel>> build() async {
    return ref.read(missionRepositoryProvider).getMissions();
  }

  /// Recharge les missions depuis l'API.
  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(missionRepositoryProvider).getMissions(),
    );
  }

  /// Réclame la récompense de la mission [id].
  ///
  /// Retourne le résultat et rafraîchit la liste.
  /// Lance une exception en cas d'erreur.
  Future<MissionClaimResult> claimReward(int id) async {
    final result = await ref.read(missionRepositoryProvider).claimReward(id);

    // Rafraîchir la liste après la réclamation
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(missionRepositoryProvider).getMissions(),
    );

    return result;
  }
}
