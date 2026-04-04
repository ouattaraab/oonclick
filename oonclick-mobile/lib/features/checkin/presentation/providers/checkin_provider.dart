import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_exception.dart';
import '../../data/models/checkin_model.dart';
import '../../data/repositories/checkin_repository.dart';

// ---------------------------------------------------------------------------
// Checkin status notifier
// ---------------------------------------------------------------------------

class CheckinNotifier extends AsyncNotifier<CheckinStatusModel> {
  @override
  Future<CheckinStatusModel> build() async {
    return ref.read(checkinRepositoryProvider).getStatus();
  }

  /// Effectue le check-in et retourne le résultat.
  ///
  /// Met à jour le statut après succès.
  Future<CheckinResultModel> performCheckin() async {
    final result =
        await ref.read(checkinRepositoryProvider).checkin();

    // Rafraîchir le statut après le check-in réussi.
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(checkinRepositoryProvider).getStatus(),
    );

    return result;
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(checkinRepositoryProvider).getStatus(),
    );
  }
}

final checkinProvider =
    AsyncNotifierProvider<CheckinNotifier, CheckinStatusModel>(
        CheckinNotifier.new);
