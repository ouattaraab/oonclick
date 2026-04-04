// ---------------------------------------------------------------------------
// Modèles de configuration de la plateforme
// ---------------------------------------------------------------------------

/// Configuration d'un format publicitaire retournée par
/// GET /api/config/campaign-formats.
class CampaignFormatConfig {
  const CampaignFormatConfig({
    required this.slug,
    required this.label,
    required this.description,
    required this.multiplier,
    required this.acceptedMedia,
    required this.isActive,
    required this.sortOrder,
    this.icon,
    this.defaultDuration,
  });

  /// Identifiant technique (ex. "video", "flash", "quiz", "scratch").
  final String slug;

  /// Libellé affiché à l'utilisateur (ex. "Vidéo").
  final String label;

  final String description;

  /// Icône Material (nom de la valeur, facultatif).
  final String? icon;

  /// Multiplicateur appliqué au coût par vue pour ce format.
  final double multiplier;

  /// Durée par défaut en secondes (null si non applicable).
  final int? defaultDuration;

  /// Types de médias acceptés (ex. ["video/mp4", "image/jpeg"]).
  final List<String> acceptedMedia;

  final bool isActive;
  final int sortOrder;

  factory CampaignFormatConfig.fromJson(Map<String, dynamic> json) {
    final rawMedia = json['accepted_media'];
    final List<String> media;
    if (rawMedia is List) {
      media = rawMedia.map((e) => e.toString()).toList();
    } else {
      media = const [];
    }

    return CampaignFormatConfig(
      slug: json['slug'] as String? ?? '',
      label: json['label'] as String? ?? '',
      description: json['description'] as String? ?? '',
      icon: json['icon'] as String?,
      multiplier: (json['multiplier'] as num?)?.toDouble() ?? 1.0,
      defaultDuration: (json['default_duration'] as num?)?.toInt(),
      acceptedMedia: media,
      isActive: json['is_active'] as bool? ?? true,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
        'slug': slug,
        'label': label,
        'description': description,
        if (icon != null) 'icon': icon,
        'multiplier': multiplier,
        if (defaultDuration != null) 'default_duration': defaultDuration,
        'accepted_media': acceptedMedia,
        'is_active': isActive,
        'sort_order': sortOrder,
      };
}

// ---------------------------------------------------------------------------

/// Configuration d'un critère d'audience retournée par
/// GET /api/config/audience-criteria.
class AudienceCriterionConfig {
  const AudienceCriterionConfig({
    required this.id,
    required this.name,
    required this.label,
    required this.type,
    required this.isRequiredForProfile,
    required this.isActive,
    required this.sortOrder,
    this.options,
    this.category,
    this.storageColumn,
  });

  final int id;

  /// Clé technique (ex. "profession", "income_range").
  final String name;

  /// Libellé affiché à l'utilisateur.
  final String label;

  /// Type de widget : "text", "select", "multiselect", "number", "range",
  /// "boolean".
  final String type;

  /// Valeurs possibles pour les types select / multiselect.
  final List<String>? options;

  /// Catégorie de regroupement optionnelle.
  final String? category;

  /// Si non null, la valeur est stockée dans une colonne dédiée du profil
  /// (critère natif). Si null, la valeur est stockée dans custom_fields.
  final String? storageColumn;

  final bool isRequiredForProfile;
  final bool isActive;
  final int sortOrder;

  /// Un critère est « natif » s'il est adossé à une colonne dédiée en base.
  /// Un critère est « personnalisé » (custom) s'il n'a pas de colonne dédiée.
  bool get isBuiltin => storageColumn != null;

  factory AudienceCriterionConfig.fromJson(Map<String, dynamic> json) {
    final rawOptions = json['options'];
    final List<String>? opts;
    if (rawOptions is List) {
      opts = rawOptions.map((e) => e.toString()).toList();
    } else {
      opts = null;
    }

    return AudienceCriterionConfig(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
      label: json['label'] as String? ?? '',
      type: json['type'] as String? ?? 'text',
      options: opts,
      category: json['category'] as String?,
      storageColumn: json['storage_column'] as String?,
      isRequiredForProfile: json['is_required_for_profile'] as bool? ?? false,
      isActive: json['is_active'] as bool? ?? true,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'label': label,
        'type': type,
        if (options != null) 'options': options,
        if (category != null) 'category': category,
        if (storageColumn != null) 'storage_column': storageColumn,
        'is_required_for_profile': isRequiredForProfile,
        'is_active': isActive,
        'sort_order': sortOrder,
      };
}
