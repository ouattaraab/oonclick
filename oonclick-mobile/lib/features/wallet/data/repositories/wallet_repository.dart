import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../models/wallet_model.dart';
import '../models/withdrawal_model.dart';

/// Repository for all wallet-related API calls.
class WalletRepository {
  WalletRepository(this._api);

  final ApiClient _api;

  // ---------------------------------------------------------------------------
  // Get wallet
  // ---------------------------------------------------------------------------

  /// GET /wallet
  Future<WalletModel> getWallet() async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>('/wallet');
      return WalletModel.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Get transactions (paginated)
  // ---------------------------------------------------------------------------

  /// GET /wallet/transactions?page=[page]
  Future<PaginatedResult<TransactionModel>> getTransactions({
    int page = 1,
  }) async {
    try {
      final response =
          await _api.get<Map<String, dynamic>>(
        '/wallet/transactions',
        params: {'page': page},
      );
      final data = response.data as Map<String, dynamic>;
      return PaginatedResult.fromJson(data, TransactionModel.fromJson);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Withdraw
  // ---------------------------------------------------------------------------

  /// POST /wallet/withdraw
  ///
  /// [amount] must be ≥ [AppConfig.minWithdrawal].
  /// [operator] is `MTN` | `Moov` | `Orange`.
  Future<void> withdraw({
    required int amount,
    required String operator,
    required String phone,
  }) async {
    try {
      await _api.post('/wallet/withdraw', data: {
        'amount': amount,
        'mobile_operator': operator,
        'mobile_phone': phone,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Withdrawal history (paginated)
  // ---------------------------------------------------------------------------

  /// GET /wallet/withdrawals?page=[page] — liste paginée des retraits.
  Future<PaginatedResult<WithdrawalModel>> getWithdrawals({
    int page = 1,
  }) async {
    try {
      final response = await _api.get<Map<String, dynamic>>(
        '/wallet/withdrawals',
        params: {'page': page},
      );
      final data = response.data as Map<String, dynamic>;
      return PaginatedResult.fromJson(data, WithdrawalModel.fromJson);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ---------------------------------------------------------------------------
  // Cancel withdrawal
  // ---------------------------------------------------------------------------

  /// POST /wallet/withdrawals/{id}/cancel — annule un retrait en attente.
  Future<void> cancelWithdrawal(int id) async {
    try {
      await _api.post<Map<String, dynamic>>(
          '/wallet/withdrawals/$id/cancel');
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final walletRepositoryProvider = Provider<WalletRepository>((ref) {
  return WalletRepository(ref.read(apiClientProvider));
});
