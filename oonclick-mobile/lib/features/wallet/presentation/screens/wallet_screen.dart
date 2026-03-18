import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/config/app_config.dart';
import '../../data/models/wallet_model.dart';
import '../providers/wallet_provider.dart';
import 'withdrawal_screen.dart';

/// Wallet screen — displays balance, statistics and recent transactions.
class WalletScreen extends ConsumerWidget {
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final walletAsync = ref.watch(walletProvider);

    return Scaffold(
      backgroundColor: AppTheme.bgPage,
      appBar: AppBar(
        title: const Text('Mon portefeuille'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.read(walletProvider.notifier).refresh(),
          ),
        ],
      ),
      body: walletAsync.when(
        loading: () => const Center(
          child: CircularProgressIndicator(color: AppTheme.primary),
        ),
        error: (err, _) => _WalletError(
          message: err.toString(),
          onRetry: () => ref.read(walletProvider.notifier).refresh(),
        ),
        data: (wallet) => RefreshIndicator(
          color: AppTheme.primary,
          onRefresh: () => ref.read(walletProvider.notifier).refresh(),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
            children: [
              // Balance card
              _BalanceCard(
                wallet: wallet,
                onWithdraw: wallet.balance >= AppConfig.minWithdrawal
                    ? () => _showWithdrawal(context, wallet)
                    : null,
              ),
              const SizedBox(height: 16),

              // Stats row
              _StatsRow(wallet: wallet),
              const SizedBox(height: 24),

              // Transaction list
              _TransactionSection(),
            ],
          ),
        ),
      ),
    );
  }

  void _showWithdrawal(BuildContext context, WalletModel wallet) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => WithdrawalSheet(currentBalance: wallet.balance),
    );
  }
}

// ---------------------------------------------------------------------------
// Balance card
// ---------------------------------------------------------------------------

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({required this.wallet, this.onWithdraw});

  final WalletModel wallet;
  final VoidCallback? onWithdraw;

  @override
  Widget build(BuildContext context) {
    final canWithdraw = wallet.balance >= AppConfig.minWithdrawal;

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1A6B3C), Color(0xFF27AE60)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppTheme.success.withAlpha(60),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              const Icon(
                Icons.account_balance_wallet_rounded,
                color: Colors.white,
                size: 22,
              ),
              const SizedBox(width: 8),
              Text(
                'Solde disponible',
                style: TextStyle(
                  color: Colors.white.withAlpha(210),
                  fontSize: 14,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            Formatters.currency(wallet.balance),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 36,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: onWithdraw,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: AppTheme.success,
                disabledBackgroundColor:
                    Colors.white.withAlpha(80),
                disabledForegroundColor:
                    Colors.white.withAlpha(160),
                minimumSize: const Size(double.infinity, 48),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              icon: const Icon(Icons.send_to_mobile_rounded, size: 18),
              label: Text(
                canWithdraw
                    ? 'Retirer mes gains'
                    : 'Minimum ${Formatters.currency(AppConfig.minWithdrawal)}',
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
          ),
          if (!canWithdraw) ...[
            const SizedBox(height: 10),
            Text(
              'Il vous manque ${Formatters.currency(AppConfig.minWithdrawal - wallet.balance)} pour retirer',
              style: TextStyle(
                color: Colors.white.withAlpha(180),
                fontSize: 12,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Stats row
// ---------------------------------------------------------------------------

class _StatsRow extends StatelessWidget {
  const _StatsRow({required this.wallet});

  final WalletModel wallet;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _StatCard(
            icon: Icons.trending_up_rounded,
            iconColor: AppTheme.primary,
            bgColor: AppTheme.primary.withAlpha(15),
            label: 'Total gagné',
            value: Formatters.currency(wallet.totalEarned),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _StatCard(
            icon: Icons.call_made_rounded,
            iconColor: const Color(0xFF6B7280),
            bgColor: AppTheme.bgPage,
            label: 'Total retiré',
            value: Formatters.currency(wallet.totalWithdrawn),
          ),
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.iconColor,
    required this.bgColor,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final Color iconColor;
  final Color bgColor;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.bgCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppTheme.divider),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconColor, size: 18),
          ),
          const SizedBox(height: 10),
          Text(
            label,
            style: const TextStyle(
              color: AppTheme.textSecondary,
              fontSize: 12,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: const TextStyle(
              color: AppTheme.textPrimary,
              fontWeight: FontWeight.w800,
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Transaction list section
// ---------------------------------------------------------------------------

class _TransactionSection extends ConsumerWidget {
  const _TransactionSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final txAsync = ref.watch(transactionsProvider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Text(
              'Transactions récentes',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 16,
                color: AppTheme.textPrimary,
              ),
            ),
            const Spacer(),
            TextButton(
              onPressed: () =>
                  ref.read(transactionsProvider.notifier).refresh(),
              child: const Text('Voir tout'),
            ),
          ],
        ),
        const SizedBox(height: 8),
        txAsync.when(
          loading: () => const _TxSkeleton(),
          error: (err, stack) => const Center(
            child: Text(
              'Erreur de chargement',
              style: TextStyle(color: AppTheme.textSecondary),
            ),
          ),
          data: (state) {
            if (state.transactions.isEmpty) {
              return const _TxEmpty();
            }

            final items = state.transactions.take(10).toList();
            return ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: items.length,
              separatorBuilder: (ctx, idx) =>
                  const Divider(height: 1),
              itemBuilder: (_, index) =>
                  _TransactionTile(tx: items[index]),
            );
          },
        ),
      ],
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.tx});

  final TransactionModel tx;

  @override
  Widget build(BuildContext context) {
    final isCredit = tx.isCredit;
    final color = isCredit ? AppTheme.success : AppTheme.error;
    final bgColor =
        isCredit ? AppTheme.successLight : AppTheme.errorLight;
    final icon = isCredit
        ? Icons.arrow_downward_rounded
        : Icons.arrow_upward_rounded;

    String formattedDate = tx.createdAt;
    try {
      final date = DateTime.parse(tx.createdAt);
      formattedDate = Formatters.dateTime(date);
    } catch (_) {}

    return ListTile(
      contentPadding:
          const EdgeInsets.symmetric(horizontal: 0, vertical: 4),
      leading: Container(
        width: 42,
        height: 42,
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Icon(icon, color: color, size: 20),
      ),
      title: Text(
        tx.description,
        style: const TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 14,
          color: AppTheme.textPrimary,
        ),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: Text(
        formattedDate,
        style: const TextStyle(
          color: AppTheme.textSecondary,
          fontSize: 12,
        ),
      ),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            isCredit
                ? '+${Formatters.currency(tx.amount)}'
                : '-${Formatters.currency(tx.amount)}',
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w800,
              fontSize: 14,
            ),
          ),
          if (tx.isPending)
            const Text(
              'En attente',
              style: TextStyle(
                color: AppTheme.warning,
                fontSize: 10,
                fontWeight: FontWeight.w600,
              ),
            )
          else if (tx.isFailed)
            const Text(
              'Échoué',
              style: TextStyle(
                color: AppTheme.error,
                fontSize: 10,
                fontWeight: FontWeight.w600,
              ),
            ),
        ],
      ),
    );
  }
}

class _TxSkeleton extends StatelessWidget {
  const _TxSkeleton();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: List.generate(
        5,
        (_) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: AppTheme.divider,
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 12,
                      width: 140,
                      color: AppTheme.divider,
                    ),
                    const SizedBox(height: 6),
                    Container(
                      height: 10,
                      width: 80,
                      color: AppTheme.divider,
                    ),
                  ],
                ),
              ),
              Container(
                height: 14,
                width: 80,
                color: AppTheme.divider,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TxEmpty extends StatelessWidget {
  const _TxEmpty();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 32),
      child: Center(
        child: Text(
          'Aucune transaction pour le moment',
          style: TextStyle(
            color: AppTheme.textSecondary,
            fontSize: 14,
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Error
// ---------------------------------------------------------------------------

class _WalletError extends StatelessWidget {
  const _WalletError({required this.message, required this.onRetry});

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
                size: 56, color: AppTheme.error),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppTheme.textSecondary),
            ),
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
