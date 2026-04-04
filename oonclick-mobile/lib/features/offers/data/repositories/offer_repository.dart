import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/offer_model.dart';

/// Repository gérant les endpoints d'offres partenaires et cashback.
class OfferRepository {
  OfferRepository(this._api);

  final ApiClient _api;

  /// GET /offers — retourne les offres partenaires actives.
  Future<List<OfferModel>> getOffers() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/offers');
      final data = (response.data!['data'] as List<dynamic>?) ?? [];
      return data
          .map((e) => OfferModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// POST /offers/{id}/claim — soumet une demande de cashback.
  Future<ClaimResult> claimOffer({
    required int offerId,
    required int purchaseAmount,
    String? receiptReference,
  }) async {
    try {
      final response = await _api.post<Map<String, dynamic>>(
        '/offers/$offerId/claim',
        data: {
          'purchase_amount': purchaseAmount,
          if (receiptReference != null && receiptReference.isNotEmpty)
            'receipt_reference': receiptReference,
        },
      );
      return ClaimResult.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final offerRepositoryProvider = Provider<OfferRepository>((ref) {
  return OfferRepository(ref.read(apiClientProvider));
});
