import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/offer_model.dart';
import '../../data/repositories/offer_repository.dart';

// ---------------------------------------------------------------------------
// Offers notifier
// ---------------------------------------------------------------------------

/// Charge et gère les offres partenaires actives.
final offersProvider =
    AsyncNotifierProvider<OffersNotifier, List<OfferModel>>(
        OffersNotifier.new);

class OffersNotifier extends AsyncNotifier<List<OfferModel>> {
  @override
  Future<List<OfferModel>> build() async {
    return ref.read(offerRepositoryProvider).getOffers();
  }

  /// Recharge les offres depuis l'API.
  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(offerRepositoryProvider).getOffers(),
    );
  }

  /// Soumet une demande de cashback.
  Future<ClaimResult> claimOffer({
    required int offerId,
    required int purchaseAmount,
    String? receiptReference,
  }) async {
    return ref.read(offerRepositoryProvider).claimOffer(
          offerId: offerId,
          purchaseAmount: purchaseAmount,
          receiptReference: receiptReference,
        );
  }
}
