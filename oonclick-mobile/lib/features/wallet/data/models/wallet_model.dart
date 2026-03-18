/// Full wallet data including transaction history.
class WalletModel {
  const WalletModel({
    required this.balance,
    required this.totalEarned,
    required this.totalWithdrawn,
    required this.recentTransactions,
  });

  /// Current spendable balance in FCFA.
  final int balance;

  /// Cumulative FCFA earned since account creation.
  final int totalEarned;

  /// Cumulative FCFA withdrawn since account creation.
  final int totalWithdrawn;

  final List<TransactionModel> recentTransactions;

  factory WalletModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    final txList = data['recent_transactions'] as List<dynamic>? ?? [];

    return WalletModel(
      balance: (data['balance'] as num?)?.toInt() ?? 0,
      totalEarned: (data['total_earned'] as num?)?.toInt() ?? 0,
      totalWithdrawn:
          (data['total_withdrawn'] as num?)?.toInt() ?? 0,
      recentTransactions: txList
          .whereType<Map<String, dynamic>>()
          .map(TransactionModel.fromJson)
          .toList(),
    );
  }
}

/// A single wallet transaction (credit, debit or bonus).
class TransactionModel {
  const TransactionModel({
    required this.id,
    required this.type,
    required this.amount,
    required this.balanceAfter,
    required this.description,
    required this.reference,
    required this.status,
    required this.createdAt,
  });

  final int id;

  /// `credit` | `debit` | `bonus`
  final String type;

  /// Amount in FCFA.
  final int amount;

  /// Wallet balance after this transaction.
  final int balanceAfter;

  final String description;
  final String reference;

  /// `pending` | `completed` | `failed`
  final String status;

  /// ISO-8601 timestamp string.
  final String createdAt;

  // ---------------------------------------------------------------------------
  // Derived helpers
  // ---------------------------------------------------------------------------

  bool get isCredit => type == 'credit' || type == 'bonus';
  bool get isDebit => type == 'debit';
  bool get isPending => status == 'pending';
  bool get isFailed => status == 'failed';

  factory TransactionModel.fromJson(Map<String, dynamic> json) {
    return TransactionModel(
      id: (json['id'] as num).toInt(),
      type: json['type'] as String? ?? 'credit',
      amount: (json['amount'] as num?)?.toInt() ?? 0,
      balanceAfter: (json['balance_after'] as num?)?.toInt() ?? 0,
      description: json['description'] as String? ?? '',
      reference: json['reference'] as String? ?? '',
      status: json['status'] as String? ?? 'completed',
      createdAt: json['created_at'] as String? ?? '',
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is TransactionModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}

/// Paginated wrapper for any data type.
class PaginatedResult<T> {
  const PaginatedResult({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<T> data;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  factory PaginatedResult.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    final rawData = json['data'] as List<dynamic>? ?? [];
    final meta = json['meta'] as Map<String, dynamic>? ??
        json['pagination'] as Map<String, dynamic>? ??
        {};

    return PaginatedResult<T>(
      data: rawData
          .whereType<Map<String, dynamic>>()
          .map(fromJson)
          .toList(),
      currentPage: (meta['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
      total: (meta['total'] as num?)?.toInt() ?? rawData.length,
    );
  }
}
