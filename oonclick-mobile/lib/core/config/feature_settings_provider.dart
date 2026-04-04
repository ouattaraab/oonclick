import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'feature_config_model.dart';
import 'platform_config_repository.dart';

export 'feature_config_model.dart';

// ---------------------------------------------------------------------------
// Fonctionnalités dynamiques
// ---------------------------------------------------------------------------

/// Fournit la liste des fonctionnalités activées sur la plateforme.
///
/// Utilisation :
/// ```dart
/// final featuresAsync = ref.watch(enabledFeaturesProvider);
/// featuresAsync.when(
///   loading: () => ...,
///   error: (e, _) => ...,
///   data: (features) => ...,
/// );
/// ```
final enabledFeaturesProvider = FutureProvider<List<FeatureConfig>>((ref) {
  return ref.read(platformConfigRepositoryProvider).getEnabledFeatures();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/// Vérifie si une fonctionnalité identifiée par son [slug] est activée.
///
/// Retourne `false` si les données ne sont pas encore chargées.
final isFeatureEnabledProvider = Provider.family<bool, String>((ref, slug) {
  final features = ref.watch(enabledFeaturesProvider).valueOrNull ?? [];
  return features.any((f) => f.slug == slug);
});

/// Retourne la configuration JSON d'une fonctionnalité identifiée par son [slug],
/// ou `null` si la fonctionnalité n'est pas activée ou pas encore chargée.
final featureConfigProvider =
    Provider.family<Map<String, dynamic>?, String>((ref, slug) {
  final features = ref.watch(enabledFeaturesProvider).valueOrNull ?? [];
  final feature = features.where((f) => f.slug == slug).firstOrNull;
  return feature?.config;
});
