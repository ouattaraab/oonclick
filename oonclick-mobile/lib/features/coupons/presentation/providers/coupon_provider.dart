import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/coupon_model.dart';
import '../../data/repositories/coupon_repository.dart';

// ---------------------------------------------------------------------------
// Coupons notifier
// ---------------------------------------------------------------------------

/// Charge et gère les coupons collectés par l'utilisateur.
final couponsProvider =
    AsyncNotifierProvider<CouponsNotifier, List<UserCouponModel>>(
        CouponsNotifier.new);

class CouponsNotifier extends AsyncNotifier<List<UserCouponModel>> {
  @override
  Future<List<UserCouponModel>> build() async {
    return ref.read(couponRepositoryProvider).getCoupons();
  }

  /// Recharge les coupons depuis l'API.
  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(couponRepositoryProvider).getCoupons(),
    );
  }

  /// Marque le coupon [userCouponId] comme utilisé.
  Future<String> markUsed(int userCouponId) async {
    final message =
        await ref.read(couponRepositoryProvider).markUsed(userCouponId);

    // Rafraîchir la liste
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(couponRepositoryProvider).getCoupons(),
    );

    return message;
  }
}
