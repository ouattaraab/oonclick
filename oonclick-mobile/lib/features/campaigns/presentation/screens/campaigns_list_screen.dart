import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../providers/campaign_provider.dart';
import '../widgets/campaign_card.dart';
import '../widgets/campaigns_empty_state.dart';

// ---------------------------------------------------------------------------
// Définition des onglets de filtre
// ---------------------------------------------------------------------------

class _FilterTab {
  const _FilterTab({required this.label, this.apiValue});
  final String label;

  /// Valeur envoyée à l'API (?status=). Null = toutes les campagnes.
  final String? apiValue;
}

const _filterTabs = [
  _FilterTab(label: 'Toutes'),
  _FilterTab(label: 'Brouillon', apiValue: 'draft'),
  _FilterTab(label: 'En attente', apiValue: 'pending_review'),
  _FilterTab(label: 'Active', apiValue: 'active'),
  _FilterTab(label: 'Terminée', apiValue: 'completed'),
];

// ---------------------------------------------------------------------------
// CampaignsListScreen
// ---------------------------------------------------------------------------

class CampaignsListScreen extends ConsumerStatefulWidget {
  const CampaignsListScreen({super.key});

  @override
  ConsumerState<CampaignsListScreen> createState() =>
      _CampaignsListScreenState();
}

class _CampaignsListScreenState extends ConsumerState<CampaignsListScreen> {
  int _activeTabIndex = 0;
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      ref.read(campaignsProvider.notifier).loadMore();
    }
  }

  Future<void> _applyFilter(int index) async {
    if (_activeTabIndex == index) return;
    setState(() => _activeTabIndex = index);
    await ref
        .read(campaignsProvider.notifier)
        .applyFilter(_filterTabs[index].apiValue);
  }

  @override
  Widget build(BuildContext context) {
    final campaignsAsync = ref.watch(campaignsProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // En-tête navy
          _CampaignsTopBar(),

          // Onglets de filtre
          _FilterTabsRow(
            activeIndex: _activeTabIndex,
            onTabSelected: _applyFilter,
          ),

          // Corps
          Expanded(
            child: campaignsAsync.when(
              loading: () => const Center(
                child:
                    CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _ErrorState(
                message: err.toString(),
                onRetry: () =>
                    ref.read(campaignsProvider.notifier).refresh(),
              ),
              data: (state) {
                if (state.campaigns.isEmpty) {
                  return CampaignsEmptyState(
                    filtered: _activeTabIndex != 0,
                    onCreateTap: () => context.push('/campaigns/new'),
                  );
                }
                return RefreshIndicator(
                  color: AppColors.sky,
                  onRefresh: () =>
                      ref.read(campaignsProvider.notifier).refresh(),
                  child: ListView.builder(
                    controller: _scrollController,
                    padding:
                        const EdgeInsets.fromLTRB(16, 12, 16, 100),
                    itemCount: state.campaigns.length +
                        (state.isLoadingMore ? 1 : 0),
                    itemBuilder: (ctx, i) {
                      if (i == state.campaigns.length) {
                        return const Padding(
                          padding: EdgeInsets.symmetric(vertical: 16),
                          child: Center(
                            child: CircularProgressIndicator(
                              color: AppColors.sky,
                              strokeWidth: 2,
                            ),
                          ),
                        );
                      }
                      final campaign = state.campaigns[i];
                      return CampaignCard(
                        campaign: campaign,
                        onTap: () =>
                            context.push('/campaigns/${campaign.id}'),
                      );
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),

      // Bouton créer
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/campaigns/new'),
        backgroundColor: AppColors.sky,
        foregroundColor: Colors.white,
        elevation: 3,
        icon: const Icon(Icons.add_rounded, size: 22),
        label: Text(
          'Nouvelle campagne',
          style: GoogleFonts.nunito(
            fontWeight: FontWeight.w700,
            fontSize: 14,
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// En-tête
// ---------------------------------------------------------------------------

class _CampaignsTopBar extends StatelessWidget {
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
            onTap: () => GoRouter.of(context).pop(),
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
            'Mes campagnes',
            style: GoogleFonts.nunito(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          // Indicateur de compte total (optionnel)
          Consumer(
            builder: (context, ref, child) {
              final state = ref.watch(campaignsProvider).valueOrNull;
              if (state == null || state.campaigns.isEmpty) {
                return const SizedBox.shrink();
              }
              return Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withAlpha(30),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '${state.campaigns.length}',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Onglets de filtre
// ---------------------------------------------------------------------------

class _FilterTabsRow extends StatelessWidget {
  const _FilterTabsRow({
    required this.activeIndex,
    required this.onTabSelected,
  });

  final int activeIndex;
  final void Function(int) onTabSelected;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      child: Column(
        children: [
          SizedBox(
            height: 44,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _filterTabs.length,
              separatorBuilder: (context, index) => const SizedBox(width: 6),
              itemBuilder: (context, i) {
                final tab = _filterTabs[i];
                final active = i == activeIndex;
                return GestureDetector(
                  onTap: () => onTabSelected(i),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    alignment: Alignment.center,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 6),
                    decoration: BoxDecoration(
                      gradient:
                          active ? AppColors.skyGradient : null,
                      color: active ? null : Colors.transparent,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      tab.label,
                      style: GoogleFonts.nunito(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color:
                            active ? Colors.white : AppColors.muted,
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
          const Divider(height: 1, thickness: 1, color: AppColors.border),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// État d'erreur
// ---------------------------------------------------------------------------

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});
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
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(color: AppColors.muted),
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
