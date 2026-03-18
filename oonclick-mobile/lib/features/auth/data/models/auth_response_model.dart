import 'user_model.dart';

/// Top-level response returned by `/auth/verify-otp`.
class AuthResponseModel {
  const AuthResponseModel({
    required this.token,
    required this.user,
    this.wallet,
  });

  final String token;
  final UserModel user;
  final WalletSummaryModel? wallet;

  factory AuthResponseModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;

    return AuthResponseModel(
      token: data['token'] as String,
      user: UserModel.fromJson(data['user'] as Map<String, dynamic>),
      wallet: data['wallet'] != null
          ? WalletSummaryModel.fromJson(
              data['wallet'] as Map<String, dynamic>)
          : null,
    );
  }
}

/// Minimal wallet information bundled inside the auth response.
class WalletSummaryModel {
  const WalletSummaryModel({
    required this.balance,
    required this.totalEarned,
  });

  /// Current balance in FCFA (integer).
  final int balance;

  /// Cumulative FCFA earned since account creation.
  final int totalEarned;

  factory WalletSummaryModel.fromJson(Map<String, dynamic> json) {
    return WalletSummaryModel(
      balance: (json['balance'] as num?)?.toInt() ?? 0,
      totalEarned: (json['total_earned'] as num?)?.toInt() ?? 0,
    );
  }
}
