import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'platform_config_model.dart';
import 'platform_config_repository.dart';

// ---------------------------------------------------------------------------
// Formats de campagne
// ---------------------------------------------------------------------------

/// Fournit la liste des formats publicitaires depuis l'API.
///
/// Utilisation :
/// ```dart
/// final formatsAsync = ref.watch(campaignFormatsProvider);
/// formatsAsync.when(
///   loading: () => ...,
///   error: (e, _) => ...,
///   data: (formats) => ...,
/// );
/// ```
final campaignFormatsProvider =
    FutureProvider<List<CampaignFormatConfig>>((ref) {
  return ref
      .read(platformConfigRepositoryProvider)
      .getCampaignFormats();
});

// ---------------------------------------------------------------------------
// Critères d'audience
// ---------------------------------------------------------------------------

/// Fournit la liste complète des critères d'audience depuis l'API.
///
/// Utilisation :
/// ```dart
/// final criteriaAsync = ref.watch(audienceCriteriaProvider);
/// ```
final audienceCriteriaProvider =
    FutureProvider<List<AudienceCriterionConfig>>((ref) {
  return ref
      .read(platformConfigRepositoryProvider)
      .getAudienceCriteria();
});
