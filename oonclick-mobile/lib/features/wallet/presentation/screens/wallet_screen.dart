import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/wallet_model.dart';
import '../providers/wallet_provider.dart';

/// Wallet screen — balance, quick actions, transaction history.
class WalletScreen extends ConsumerStatefulWidget {
  const WalletScreen({super.key});

  @override
  ConsumerState<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends ConsumerState<WalletScreen> {
  String _activeFilter = 'Tout';
  final _filters = ['Tout', 'Ce mois', 'Crédits', 'Retraits'];

  @override
  Widget build(BuildContext context) {
    final walletAsync = ref.watch(walletProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // Navy top bar
          _WalletTopBar(),

          Expanded(
            child: walletAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _WalletError(
                message: err.toString(),
                onRetry: () => ref.read(walletProvider.notifier).refresh(),
              ),
              data: (wallet) => RefreshIndicator(
                color: AppColors.sky,
                onRefresh: () => ref.read(walletProvider.notifier).refresh(),
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 32),
                  children: [
                    const SizedBox(height: 16),

                    // Balance card
                    _BalanceCard(wallet: wallet),
                    const SizedBox(height: 16),

                    // Quick actions
                    _QuickActions(wallet: wallet),
                    const SizedBox(height: 20),

                    // Transaction history header
                    Text(
                      'Historique',
                      style: GoogleFonts.nunito(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: AppColors.navy,
                      ),
                    ),
                    const SizedBox(height: 10),

                    // Filter tabs
                    SizedBox(
                      height: 36,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _filters.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 8),
                        itemBuilder: (ctx, i) {
                          final f = _filters[i];
                          final active = f == _activeFilter;
                          return GestureDetector(
                            onTap: () => setState(() => _activeFilter = f),
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 200),
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 14, vertical: 7),
                              decoration: BoxDecoration(
                                gradient: active ? AppColors.skyGradient : null,
                                color: active ? null : Colors.white,
                                borderRadius: BorderRadius.circular(18),
                                border: Border.all(
                                  color: active
                                      ? Colors.transparent
                                      : AppColors.border,
                                ),
                              ),
                              child: Text(
                                f,
                                style: GoogleFonts.nunito(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: active ? Colors.white : AppColors.muted,
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Transaction list
                    _TransactionSection(filter: _activeFilter),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _WalletTopBar extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        MediaQuery.of(context).padding.top + 12,
        16,
        14,
      ),
      decoration: const BoxDecoration(
        gradient: AppColors.navyGradient,
      ),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => GoRouter.of(context).go('/feed'),
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
          RichText(
            text: TextSpan(
              children: [
                TextSpan(
                  text: 'oon',
                  style: GoogleFonts.nunito(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      color: AppColors.sky),
                ),
                TextSpan(
                  text: '.click',
                  style: GoogleFonts.nunito(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      color: Colors.white),
                ),
              ],
            ),
          ),
          const Spacer(),
          const Icon(Icons.more_vert_rounded, color: Colors.white),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Balance card
// ---------------------------------------------------------------------------

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({required this.wallet});
  final WalletModel wallet;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: AppColors.skyGradientDiagonal,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(60),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Mon solde',
            style: GoogleFonts.nunito(
              fontSize: 13,
              color: Colors.white.withAlpha(210),
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            Formatters.currency(wallet.balance),
            style: GoogleFonts.nunito(
              fontSize: 28,
              fontWeight: FontWeight.w900,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 14),
          // Stats row
          Row(
            children: [
              _GlassStat(label: 'Aujourd\'hui', value: '+600 F'),
              const SizedBox(width: 10),
              _GlassStat(
                label: 'Semaine',
                value: '+${Formatters.currency(wallet.totalEarned ~/ 4)}',
              ),
              const SizedBox(width: 10),
              _GlassStat(
                label: 'Mois',
                value: Formatters.currency(wallet.totalEarned),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _GlassStat extends StatelessWidget {
  const _GlassStat({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        decoration: BoxDecoration(
          color: Colors.white.withAlpha(35),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: GoogleFonts.nunito(
                fontSize: 10,
                color: Colors.white.withAlpha(180),
              ),
            ),
            Text(
              value,
              style: GoogleFonts.nunito(
                fontSize: 12,
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Quick actions
// ---------------------------------------------------------------------------

class _QuickActions extends StatelessWidget {
  const _QuickActions({required this.wallet});
  final WalletModel wallet;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _ActionCard(
            emoji: '💳',
            label: 'Retirer',
            onTap: wallet.balance >= AppConfig.minWithdrawal
                ? () => context.push('/withdrawal')
                : null,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _ActionCard(
            emoji: '📊',
            label: 'Historique',
            onTap: () => context.push('/wallet/history'),
          ),
        ),
      ],
    );
  }
}

class _ActionCard extends StatelessWidget {
  const _ActionCard({
    required this.emoji,
    required this.label,
    required this.onTap,
  });
  final String emoji;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final disabled = onTap == null;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(
          color: disabled ? AppColors.bg : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          children: [
            Text(emoji, style: const TextStyle(fontSize: 22)),
            const SizedBox(height: 6),
            Text(
              label,
              style: GoogleFonts.nunito(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: disabled ? AppColors.muted : AppColors.navy,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Transaction section
// ---------------------------------------------------------------------------

class _TransactionSection extends ConsumerWidget {
  const _TransactionSection({required this.filter});
  final String filter;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final txAsync = ref.watch(transactionsProvider);
    return txAsync.when(
      loading: () => const _TxSkeleton(),
      error: (_, __) => Center(
        child: Text('Erreur de chargement',
            style: GoogleFonts.nunito(color: AppColors.muted)),
      ),
      data: (state) {
        var items = state.transactions.take(20).toList();
        if (filter == 'Crédits') {
          items = items.where((t) => t.isCredit).toList();
        } else if (filter == 'Retraits') {
          items = items.where((t) => t.isDebit).toList();
        }

        if (items.isEmpty) {
          return Padding(
            padding: const EdgeInsets.symmetric(vertical: 32),
            child: Center(
              child: Text(
                'Aucune transaction',
                style: GoogleFonts.nunito(color: AppColors.muted),
              ),
            ),
          );
        }
        return Column(
          children: items
              .map((tx) => _TransactionTile(tx: tx))
              .toList(),
        );
      },
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.tx});
  final TransactionModel tx;

  @override
  Widget build(BuildContext context) {
    final isCredit = tx.isCredit;
    final isBonus = tx.type == 'bonus';

    final (bgColor, iconColor, icon) = isBonus
        ? (
            const Color(0xFFFEF3C7),
            AppColors.warn,
            Icons.star_rounded
          )
        : isCredit
            ? (AppColors.successLight, AppColors.success, Icons.arrow_downward_rounded)
            : (AppColors.dangerLight, AppColors.danger, Icons.arrow_upward_rounded);

    String formattedDate = tx.createdAt;
    try {
      final date = DateTime.parse(tx.createdAt);
      formattedDate = Formatters.dateTime(date);
    } catch (_) {}

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  tx.description,
                  style: GoogleFonts.nunito(
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                    color: AppColors.navy,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  formattedDate,
                  style: GoogleFonts.nunito(
                    fontSize: 11,
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
                isCredit
                    ? '+${Formatters.currency(tx.amount)}'
                    : '-${Formatters.currency(tx.amount)}',
                style: GoogleFonts.nunito(
                  color: isCredit ? AppColors.success : AppColors.danger,
                  fontWeight: FontWeight.w800,
                  fontSize: 13,
                ),
              ),
              if (tx.isPending)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    'En attente',
                    style: GoogleFonts.nunito(
                      fontSize: 10,
                      color: AppColors.warn,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
            ],
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
      children: List.generate(5, (_) {
        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          height: 66,
          decoration: BoxDecoration(
            color: AppColors.border.withAlpha(80),
            borderRadius: BorderRadius.circular(12),
          ),
        );
      }),
    );
  }
}

// ---------------------------------------------------------------------------
// Error state
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
