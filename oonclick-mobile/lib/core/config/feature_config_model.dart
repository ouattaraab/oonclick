/// Modèle représentant une fonctionnalité dynamique activée sur la plateforme.
class FeatureConfig {
  final String slug;
  final String label;
  final Map<String, dynamic> config;

  const FeatureConfig({
    required this.slug,
    required this.label,
    required this.config,
  });

  factory FeatureConfig.fromJson(Map<String, dynamic> json) {
    return FeatureConfig(
      slug:   json['slug'] as String,
      label:  json['label'] as String,
      config: (json['config'] as Map<String, dynamic>?) ?? {},
    );
  }
}
