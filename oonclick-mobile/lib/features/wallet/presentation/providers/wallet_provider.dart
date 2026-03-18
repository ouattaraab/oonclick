import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/wallet_model.dart';
import '../../data/repositories/wallet_repository.dart';

// ---------------------------------------------------------------------------
// Wallet notifier
// ---------------------------------------------------------------------------

class WalletNotifier extends AsyncNotifier<WalletModel> {
  @override
  Future<WalletModel> build() async {
    return ref.read(walletRepositoryProvider).getWallet();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(walletRepositoryProvider).getWallet(),
    );
  }

  Future<void> withdraw({
    required int amount,
    required String operator,
    required String phone,
  }) async {
    await ref.read(walletRepositoryProvider).withdraw(
          amount: amount,
          operator: operator,
          phone: phone,
        );
    // Refresh balance after successful withdrawal.
    await refresh();
  }
}

final walletProvider =
    AsyncNotifierProvider<WalletNotifier, WalletModel>(WalletNotifier.new);

// ---------------------------------------------------------------------------
// Transactions notifier (paginated)
// ---------------------------------------------------------------------------

class TransactionsState {
  const TransactionsState({
    this.transactions = const [],
    this.currentPage = 0,
    this.hasMore = true,
    this.isLoadingMore = false,
  });

  final List<TransactionModel> transactions;
  final int currentPage;
  final bool hasMore;
  final bool isLoadingMore;

  TransactionsState copyWith({
    List<TransactionModel>? transactions,
    int? currentPage,
    bool? hasMore,
    bool? isLoadingMore,
  }) {
    return TransactionsState(
      transactions: transactions ?? this.transactions,
      currentPage: currentPage ?? this.currentPage,
      hasMore: hasMore ?? this.hasMore,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
    );
  }
}

class TransactionsNotifier
    extends AsyncNotifier<TransactionsState> {
  @override
  Future<TransactionsState> build() async {
    return _loadPage(1);
  }

  Future<TransactionsState> _loadPage(int page) async {
    final result = await ref
        .read(walletRepositoryProvider)
        .getTransactions(page: page);

    return TransactionsState(
      transactions: result.data,
      currentPage: result.currentPage,
      hasMore: result.hasMore,
    );
  }

  Future<void> loadMore() async {
    final current = state.valueOrNull;
    if (current == null || !current.hasMore || current.isLoadingMore) {
      return;
    }

    state =
        AsyncData(current.copyWith(isLoadingMore: true));

    try {
      final result = await ref
          .read(walletRepositoryProvider)
          .getTransactions(page: current.currentPage + 1);

      state = AsyncData(TransactionsState(
        transactions: [...current.transactions, ...result.data],
        currentPage: result.currentPage,
        hasMore: result.hasMore,
      ));
    } catch (e) {
      state = AsyncData(current.copyWith(isLoadingMore: false));
    }
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => _loadPage(1));
  }
}

final transactionsProvider =
    AsyncNotifierProvider<TransactionsNotifier, TransactionsState>(
        TransactionsNotifier.new);
