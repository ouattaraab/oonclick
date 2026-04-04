import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/coupon_model.dart';

/// Repository gérant les endpoints de coupons collectés.
class CouponRepository {
  CouponRepository(this._api);

  final ApiClient _api;

  /// GET /coupons — retourne les coupons collectés par l'utilisateur.
  Future<List<UserCouponModel>> getCoupons() async {
    try {
      final response = await _api.get<Map<String, dynamic>>('/coupons');
      final data = (response.data!['data'] as List<dynamic>?) ?? [];
      return data
          .map((e) => UserCouponModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// POST /coupons/{id}/use — marque un coupon comme utilisé.
  Future<String> markUsed(int userCouponId) async {
    try {
      final response = await _api
          .post<Map<String, dynamic>>('/coupons/$userCouponId/use');
      return (response.data as Map<String, dynamic>?)?['message'] as String? ??
          'Coupon utilisé.';
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final couponRepositoryProvider = Provider<CouponRepository>((ref) {
  return CouponRepository(ref.read(apiClientProvider));
});
