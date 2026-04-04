import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/withdrawal_model.dart';
import '../../data/repositories/wallet_repository.dart';
import '../../../auth/presentation/providers/auth_provider.dart';

// ---------------------------------------------------------------------------
// Provider for withdrawal history
// ---------------------------------------------------------------------------

class WithdrawalHistoryState {
  const WithdrawalHistoryState({
    this.withdrawals = const [],
    this.currentPage = 0,
    this.hasMore = true,
    this.isLoadingMore = false,
  });

  final List<WithdrawalModel> withdrawals;
  final int currentPage;
  final bool hasMore;
  final bool isLoadingMore;

  WithdrawalHistoryState copyWith({
    List<WithdrawalModel>? withdrawals,
    int? currentPage,
    bool? hasMore,
    bool? isLoadingMore,
  }) {
    return WithdrawalHistoryState(
      withdrawals: withdrawals ?? this.withdrawals,
      currentPage: currentPage ?? this.currentPage,
      hasMore: hasMore ?? this.hasMore,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
    );
  }
}

class WithdrawalHistoryNotifier
    extends AsyncNotifier<WithdrawalHistoryState> {
  @override
  Future<WithdrawalHistoryState> build() async {
    return _loadPage(1);
  }

  Future<WithdrawalHistoryState> _loadPage(int page) async {
    final result = await ref
        .read(walletRepositoryProvider)
        .getWithdrawals(page: page);
    return WithdrawalHistoryState(
      withdrawals: result.data,
      currentPage: result.currentPage,
      hasMore: result.hasMore,
    );
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => _loadPage(1));
  }

  Future<void> loadMore() async {
    final current = state.valueOrNull;
    if (current == null || !current.hasMore || current.isLoadingMore) {
      return;
    }
    state = AsyncData(current.copyWith(isLoadingMore: true));
    try {
      final result = await ref
          .read(walletRepositoryProvider)
          .getWithdrawals(page: current.currentPage + 1);
      state = AsyncData(WithdrawalHistoryState(
        withdrawals: [...current.withdrawals, ...result.data],
        currentPage: result.currentPage,
        hasMore: result.hasMore,
      ));
    } catch (_) {
      state = AsyncData(current.copyWith(isLoadingMore: false));
    }
  }

  Future<void> cancel(int id) async {
    await ref.read(walletRepositoryProvider).cancelWithdrawal(id);
    await refresh();
  }
}

final withdrawalHistoryProvider = AsyncNotifierProvider<
    WithdrawalHistoryNotifier, WithdrawalHistoryState>(
  WithdrawalHistoryNotifier.new,
);

// ---------------------------------------------------------------------------
// Screen
// ---------------------------------------------------------------------------

/// Écran d'historique des retraits avec possibilité d'annuler les retraits en attente.
class WithdrawalHistoryScreen extends ConsumerWidget {
  const WithdrawalHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final historyAsync = ref.watch(withdrawalHistoryProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _TopBar(),
          Expanded(
            child: historyAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _ErrorView(
                message: err.toString(),
                onRetry: () =>
                    ref.read(withdrawalHistoryProvider.notifier).refresh(),
              ),
              data: (state) => RefreshIndicator(
                color: AppColors.sky,
                onRefresh: () =>
                    ref.read(withdrawalHistoryProvider.notifier).refresh(),
                child: state.withdrawals.isEmpty
                    ? _EmptyView()
                    : ListView.builder(
                        padding:
                            const EdgeInsets.fromLTRB(16, 16, 16, 40),
                        itemCount: state.withdrawals.length +
                            (state.hasMore ? 1 : 0),
                        itemBuilder: (context, index) {
                          if (index == state.withdrawals.length) {
                            // Load more trigger.
                            WidgetsBinding.instance
                                .addPostFrameCallback((_) {
                              ref
                                  .read(withdrawalHistoryProvider.notifier)
                                  .loadMore();
                            });
                            return const Center(
                              child: Padding(
                                padding: EdgeInsets.all(16),
                                child: CircularProgressIndicator(
                                    color: AppColors.sky),
                              ),
                            );
                          }
                          return _WithdrawalTile(
                            withdrawal: state.withdrawals[index],
                            onCancel: state.withdrawals[index].canBeCancelled
                                ? () => _confirmCancel(context, ref,
                                    state.withdrawals[index].id)
                                : null,
                          );
                        },
                      ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _confirmCancel(BuildContext context, WidgetRef ref, int id) {
    showDialog<void>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(
          'Annuler le retrait',
          style: GoogleFonts.nunito(fontWeight: FontWeight.w800),
        ),
        content: Text(
          'Voulez-vous vraiment annuler ce retrait ? Le montant sera recrédité sur votre wallet.',
          style: GoogleFonts.nunito(),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Non'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.danger),
            onPressed: () async {
              Navigator.of(context).pop();
              try {
                await ref
                    .read(withdrawalHistoryProvider.notifier)
                    .cancel(id);
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Retrait annulé.',
                          style: GoogleFonts.nunito()),
                      backgroundColor: AppColors.success,
                    ),
                  );
                }
              } catch (e) {
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                          e.toString().replaceAll('Exception: ', ''),
                          style: GoogleFonts.nunito()),
                      backgroundColor: AppColors.danger,
                    ),
                  );
                }
              }
            },
            child: Text('Oui, annuler',
                style: GoogleFonts.nunito(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _TopBar extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        MediaQuery.of(context).padding.top + 12,
        16,
        14,
      ),
      decoration: const BoxDecoration(gradient: AppColors.navyGradient),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => context.pop(),
            child: Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(30),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.arrow_back_ios_new_rounded,
                  color: Colors.white, size: 15),
            ),
          ),
          const SizedBox(width: 12),
          Text(
            'Historique des retraits',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Withdrawal tile
// ---------------------------------------------------------------------------

class _WithdrawalTile extends StatelessWidget {
  const _WithdrawalTile({
    required this.withdrawal,
    required this.onCancel,
  });

  final WithdrawalModel withdrawal;
  final VoidCallback? onCancel;

  Color _statusColor(WithdrawalModel w) {
    if (w.isCompleted) return AppColors.success;
    if (w.isFailed || w.isCancelled) return AppColors.danger;
    return AppColors.warn;
  }

  Color _statusBg(WithdrawalModel w) {
    if (w.isCompleted) return AppColors.successLight;
    if (w.isFailed || w.isCancelled) return AppColors.dangerLight;
    return AppColors.warnLight;
  }

  String _formatDate(String iso) {
    try {
      final dt = DateTime.parse(iso);
      return Formatters.dateTime(dt);
    } catch (_) {
      return iso;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Operator icon
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.skyPale,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    withdrawal.mobileOperator == 'MTN'
                        ? '📱'
                        : withdrawal.mobileOperator == 'Moov'
                            ? '📲'
                            : '📡',
                    style: const TextStyle(fontSize: 22),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Retrait ${withdrawal.mobileOperator}',
                      style: GoogleFonts.nunito(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: AppColors.navy,
                      ),
                    ),
                    Text(
                      withdrawal.mobilePhone,
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    Formatters.currency(withdrawal.amount),
                    style: GoogleFonts.nunito(
                      fontSize: 15,
                      fontWeight: FontWeight.w900,
                      color: AppColors.navy,
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: _statusBg(withdrawal),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      withdrawal.statusLabel,
                      style: GoogleFonts.nunito(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: _statusColor(withdrawal),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),

          // Date
          const SizedBox(height: 8),
          Row(
            children: [
              Icon(Icons.schedule_rounded, size: 12, color: AppColors.muted),
              const SizedBox(width: 4),
              Text(
                _formatDate(withdrawal.createdAt),
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  color: AppColors.muted,
                ),
              ),
              if (withdrawal.reference != null) ...[
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Réf: ${withdrawal.reference}',
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      color: AppColors.muted,
                    ),
                  ),
                ),
              ],
            ],
          ),

          // Failure reason
          if (withdrawal.failureReason != null) ...[
            const SizedBox(height: 6),
            Text(
              withdrawal.failureReason!,
              style: GoogleFonts.nunito(
                fontSize: 11,
                color: AppColors.danger,
              ),
            ),
          ],

          // Cancel button for pending withdrawals
          if (onCancel != null) ...[
            const SizedBox(height: 10),
            GestureDetector(
              onTap: onCancel,
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.dangerLight,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                      color: AppColors.danger.withAlpha(80)),
                ),
                child: Center(
                  child: Text(
                    'Annuler ce retrait',
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.danger,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Empty view
// ---------------------------------------------------------------------------

class _EmptyView extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('💳', style: TextStyle(fontSize: 48)),
          const SizedBox(height: 16),
          Text(
            'Aucun retrait effectué',
            style: GoogleFonts.nunito(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppColors.navy,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Vos retraits apparaîtront ici.',
            style: GoogleFonts.nunito(color: AppColors.muted),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Error view
// ---------------------------------------------------------------------------

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline_rounded,
                size: 56, color: AppColors.danger),
            const SizedBox(height: 16),
            Text(message,
                textAlign: TextAlign.center,
                style: GoogleFonts.nunito(color: AppColors.muted)),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: onRetry,
              child: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }
}
