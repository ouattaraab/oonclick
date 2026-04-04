import 'dart:io';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_exception.dart';
import '../../data/models/kyc_model.dart';
import '../../data/repositories/kyc_repository.dart';

// ---------------------------------------------------------------------------
// KYC status notifier
// ---------------------------------------------------------------------------

class KycNotifier extends AsyncNotifier<KycStatusModel> {
  @override
  Future<KycStatusModel> build() async {
    return ref.read(kycRepositoryProvider).getStatus();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(kycRepositoryProvider).getStatus(),
    );
  }
}

final kycProvider =
    AsyncNotifierProvider<KycNotifier, KycStatusModel>(KycNotifier.new);

// ---------------------------------------------------------------------------
// Documents provider
// ---------------------------------------------------------------------------

class KycDocumentsNotifier
    extends AsyncNotifier<List<KycDocumentModel>> {
  @override
  Future<List<KycDocumentModel>> build() async {
    return ref.read(kycRepositoryProvider).getDocuments();
  }

  Future<void> submit({
    required int level,
    required String type,
    required File file,
  }) async {
    await ref.read(kycRepositoryProvider).submitDocument(
          level: level,
          type: type,
          file: file,
        );
    // Rafraîchir la liste et le statut après soumission.
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(kycRepositoryProvider).getDocuments(),
    );
    ref.read(kycProvider.notifier).refresh().ignore();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(kycRepositoryProvider).getDocuments(),
    );
  }
}

final kycDocumentsProvider =
    AsyncNotifierProvider<KycDocumentsNotifier, List<KycDocumentModel>>(
        KycDocumentsNotifier.new);
