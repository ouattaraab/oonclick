import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/campaign_model.dart';
import '../../data/repositories/campaign_repository.dart';

// ---------------------------------------------------------------------------
// State pour la liste paginée des campagnes
// ---------------------------------------------------------------------------

class CampaignsState {
  const CampaignsState({
    this.campaigns = const [],
    this.currentPage = 0,
    this.hasMore = true,
    this.isLoadingMore = false,
    this.statusFilter,
  });

  final List<CampaignModel> campaigns;
  final int currentPage;
  final bool hasMore;
  final bool isLoadingMore;

  /// Filtre actif : null = toutes, sinon valeur API (ex. 'draft', 'active')
  final String? statusFilter;

  CampaignsState copyWith({
    List<CampaignModel>? campaigns,
    int? currentPage,
    bool? hasMore,
    bool? isLoadingMore,
    String? statusFilter,
    bool clearFilter = false,
  }) {
    return CampaignsState(
      campaigns: campaigns ?? this.campaigns,
      currentPage: currentPage ?? this.currentPage,
      hasMore: hasMore ?? this.hasMore,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      statusFilter:
          clearFilter ? null : (statusFilter ?? this.statusFilter),
    );
  }
}

// ---------------------------------------------------------------------------
// Campaigns list notifier
// ---------------------------------------------------------------------------

class CampaignsNotifier extends AsyncNotifier<CampaignsState> {
  @override
  Future<CampaignsState> build() async {
    return _loadPage(1, statusFilter: null);
  }

  Future<CampaignsState> _loadPage(
    int page, {
    required String? statusFilter,
  }) async {
    final result = await ref
        .read(campaignRepositoryProvider)
        .getCampaigns(page: page, status: statusFilter);

    return CampaignsState(
      campaigns: result.data,
      currentPage: result.currentPage,
      hasMore: result.hasMore,
      statusFilter: statusFilter,
    );
  }

  /// Charge la page suivante (infinite scroll).
  Future<void> loadMore() async {
    final current = state.valueOrNull;
    if (current == null || !current.hasMore || current.isLoadingMore) return;

    state = AsyncData(current.copyWith(isLoadingMore: true));

    try {
      final result = await ref
          .read(campaignRepositoryProvider)
          .getCampaigns(
            page: current.currentPage + 1,
            status: current.statusFilter,
          );

      state = AsyncData(CampaignsState(
        campaigns: [...current.campaigns, ...result.data],
        currentPage: result.currentPage,
        hasMore: result.hasMore,
        statusFilter: current.statusFilter,
      ));
    } catch (_) {
      state = AsyncData(current.copyWith(isLoadingMore: false));
    }
  }

  /// Applique un filtre de statut et recharge depuis la page 1.
  Future<void> applyFilter(String? statusFilter) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => _loadPage(1, statusFilter: statusFilter),
    );
  }

  /// Rafraîchit la liste depuis la page 1 avec le filtre actif.
  Future<void> refresh() async {
    final currentFilter = state.valueOrNull?.statusFilter;
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => _loadPage(1, statusFilter: currentFilter),
    );
  }

  /// Met à jour localement une campagne après une action (pause, resume…).
  void updateCampaignLocally(CampaignModel updated) {
    final current = state.valueOrNull;
    if (current == null) return;
    final index = current.campaigns.indexWhere((c) => c.id == updated.id);
    if (index == -1) return;
    final newList = List<CampaignModel>.from(current.campaigns);
    newList[index] = updated;
    state = AsyncData(current.copyWith(campaigns: newList));
  }

  /// Supprime localement une campagne après DELETE.
  void removeCampaignLocally(int campaignId) {
    final current = state.valueOrNull;
    if (current == null) return;
    final newList = current.campaigns.where((c) => c.id != campaignId).toList();
    state = AsyncData(current.copyWith(campaigns: newList));
  }
}

final campaignsProvider =
    AsyncNotifierProvider<CampaignsNotifier, CampaignsState>(
        CampaignsNotifier.new);

// ---------------------------------------------------------------------------
// Campaign detail notifier
// ---------------------------------------------------------------------------

class CampaignDetailNotifier
    extends FamilyAsyncNotifier<CampaignDetailModel, int> {
  @override
  Future<CampaignDetailModel> build(int id) async {
    return ref.read(campaignRepositoryProvider).getCampaign(id);
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(campaignRepositoryProvider).getCampaign(arg),
    );
  }

  /// Rafraîchit silencieusement sans afficher l'état de chargement.
  /// Utilisé pour le polling temps réel sur les campagnes actives.
  Future<void> refreshSilent() async {
    try {
      final detail =
          await ref.read(campaignRepositoryProvider).getCampaignProgress(arg);
      state = AsyncData(detail);
    } catch (_) {
      // Échec silencieux — on conserve les données précédentes.
    }
  }

  /// Soumet la campagne pour révision.
  Future<void> submit() async {
    final campaign =
        await ref.read(campaignRepositoryProvider).submitCampaign(arg);
    _updateLocalCampaign(campaign);
  }

  /// Met la campagne en pause.
  Future<void> pause() async {
    final campaign =
        await ref.read(campaignRepositoryProvider).pauseCampaign(arg);
    _updateLocalCampaign(campaign);
  }

  /// Reprend une campagne en pause.
  Future<void> resume() async {
    final campaign =
        await ref.read(campaignRepositoryProvider).resumeCampaign(arg);
    _updateLocalCampaign(campaign);
  }

  void _updateLocalCampaign(CampaignModel updated) {
    final current = state.valueOrNull;
    if (current == null) return;
    state = AsyncData(CampaignDetailModel(
      campaign: updated,
      viewsCount: updated.viewsCount,
      budgetUsed: updated.budgetUsed,
      remainingViews: updated.remainingViews,
    ));
    // Propage la mise à jour à la liste.
    ref.read(campaignsProvider.notifier).updateCampaignLocally(updated);
  }
}

final campaignDetailProvider = AsyncNotifierProviderFamily<
    CampaignDetailNotifier, CampaignDetailModel, int>(
  CampaignDetailNotifier.new,
);

// ---------------------------------------------------------------------------
// Campaign form notifier — création / édition
// ---------------------------------------------------------------------------

/// État du formulaire de campagne.
class CampaignFormState {
  const CampaignFormState({
    this.isSaving = false,
    this.isUploading = false,
    this.savedCampaign,
    this.errorMessage,
  });

  final bool isSaving;
  final bool isUploading;
  final CampaignModel? savedCampaign;
  final String? errorMessage;

  CampaignFormState copyWith({
    bool? isSaving,
    bool? isUploading,
    CampaignModel? savedCampaign,
    String? errorMessage,
  }) {
    return CampaignFormState(
      isSaving: isSaving ?? this.isSaving,
      isUploading: isUploading ?? this.isUploading,
      savedCampaign: savedCampaign ?? this.savedCampaign,
      errorMessage: errorMessage,
    );
  }
}

class CampaignFormNotifier extends Notifier<CampaignFormState> {
  @override
  CampaignFormState build() => const CampaignFormState();

  Future<CampaignModel?> saveDraft({
    required String title,
    String? description,
    required String format,
    required int budget,
    required int costPerView,
    Map<String, dynamic>? targeting,
    int? durationSeconds,
    int? existingId,
    String? endMode,
  }) async {
    state = state.copyWith(isSaving: true, errorMessage: null);
    try {
      final CampaignModel result;
      if (existingId != null) {
        result = await ref.read(campaignRepositoryProvider).updateCampaign(
              existingId,
              title: title,
              description: description,
              format: format,
              budget: budget,
              costPerView: costPerView,
              targeting: targeting,
              durationSeconds: durationSeconds,
              endMode: endMode,
            );
      } else {
        result = await ref.read(campaignRepositoryProvider).createCampaign(
              title: title,
              description: description,
              format: format,
              budget: budget,
              costPerView: costPerView,
              targeting: targeting,
              durationSeconds: durationSeconds,
              endMode: endMode,
            );
      }
      state = state.copyWith(isSaving: false, savedCampaign: result);
      ref.read(campaignsProvider.notifier).refresh();
      return result;
    } catch (e) {
      state = state.copyWith(isSaving: false, errorMessage: e.toString());
      return null;
    }
  }

  Future<bool> uploadMedia(
    int campaignId, {
    required String mediaFilePath,
    String? thumbnailFilePath,
  }) async {
    state = state.copyWith(isUploading: true, errorMessage: null);
    try {
      final updated = await ref.read(campaignRepositoryProvider).uploadMedia(
            campaignId,
            mediaFilePath: mediaFilePath,
            thumbnailFilePath: thumbnailFilePath,
          );
      state = state.copyWith(isUploading: false, savedCampaign: updated);
      ref.read(campaignsProvider.notifier).updateCampaignLocally(updated);
      return true;
    } catch (e) {
      state = state.copyWith(isUploading: false, errorMessage: e.toString());
      return false;
    }
  }

  void clearError() => state = state.copyWith(errorMessage: null);
  void reset() => state = const CampaignFormState();
}

final campaignFormProvider =
    NotifierProvider<CampaignFormNotifier, CampaignFormState>(
        CampaignFormNotifier.new);
