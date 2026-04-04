// ---------------------------------------------------------------------------
// Campaign formats
// ---------------------------------------------------------------------------

/// Format publicitaire d'une campagne.
enum CampaignFormat {
  video,
  scratch,
  quiz,
  flash;

  /// Libellé en français.
  String get label => switch (this) {
        CampaignFormat.video => 'Vidéo',
        CampaignFormat.scratch => 'Grattage',
        CampaignFormat.quiz => 'Quiz',
        CampaignFormat.flash => 'Flash',
      };

  static CampaignFormat fromString(String? value) => switch (value) {
        'video' => CampaignFormat.video,
        'scratch' => CampaignFormat.scratch,
        'quiz' => CampaignFormat.quiz,
        'flash' => CampaignFormat.flash,
        _ => CampaignFormat.video,
      };
}

// ---------------------------------------------------------------------------
// Campaign statuses
// ---------------------------------------------------------------------------

/// Statut d'une campagne.
enum CampaignStatus {
  draft,
  pendingReview,
  approved,
  active,
  paused,
  completed,
  rejected;

  /// Libellé en français.
  String get label => switch (this) {
        CampaignStatus.draft => 'Brouillon',
        CampaignStatus.pendingReview => 'En attente',
        CampaignStatus.approved => 'Approuvée',
        CampaignStatus.active => 'Active',
        CampaignStatus.paused => 'En pause',
        CampaignStatus.completed => 'Terminée',
        CampaignStatus.rejected => 'Rejetée',
      };

  static CampaignStatus fromString(String? value) => switch (value) {
        'draft' => CampaignStatus.draft,
        'pending_review' => CampaignStatus.pendingReview,
        'approved' => CampaignStatus.approved,
        'active' => CampaignStatus.active,
        'paused' => CampaignStatus.paused,
        'completed' => CampaignStatus.completed,
        'rejected' => CampaignStatus.rejected,
        _ => CampaignStatus.draft,
      };
}

// ---------------------------------------------------------------------------
// CampaignModel
// ---------------------------------------------------------------------------

/// Modèle complet d'une campagne publicitaire.
class CampaignModel {
  const CampaignModel({
    required this.id,
    required this.advertiserId,
    required this.title,
    required this.format,
    required this.status,
    required this.budget,
    required this.costPerView,
    required this.maxViews,
    required this.viewsCount,
    required this.createdAt,
    this.description,
    this.mediaUrl,
    this.thumbnailUrl,
    this.durationSeconds,
    this.targeting,
    this.startsAt,
    this.endsAt,
    this.approvedAt,
    this.approvedBy,
    this.rejectionReason,
    this.updatedAt,
    this.endMode,
  });

  final int id;
  final int advertiserId;
  final String title;
  final String? description;
  final CampaignFormat format;
  final CampaignStatus status;

  /// Budget total en FCFA.
  final int budget;

  /// Coût par vue en FCFA.
  final int costPerView;

  /// Nombre maximum de vues autorisées.
  final int maxViews;

  /// Nombre de vues déjà enregistrées.
  final int viewsCount;

  final String? mediaUrl;
  final String? thumbnailUrl;

  /// Durée en secondes (pour les formats vidéo).
  final int? durationSeconds;

  /// Ciblage : ages, genres, intérêts, etc.
  final Map<String, dynamic>? targeting;

  final String? startsAt;
  final String? endsAt;
  final String? approvedAt;
  final int? approvedBy;
  final String? rejectionReason;
  final String createdAt;
  final String? updatedAt;

  /// Mode de fin de campagne : 'date', 'target_reached', ou 'manual'.
  final String? endMode;

  // ---------------------------------------------------------------------------
  // Derived helpers
  // ---------------------------------------------------------------------------

  bool get isDraft => status == CampaignStatus.draft;
  bool get isPendingReview => status == CampaignStatus.pendingReview;
  bool get isApproved => status == CampaignStatus.approved;
  bool get isActive => status == CampaignStatus.active;
  bool get isPaused => status == CampaignStatus.paused;
  bool get isCompleted => status == CampaignStatus.completed;
  bool get isRejected => status == CampaignStatus.rejected;

  /// Vues restantes avant épuisement du budget.
  int get remainingViews => (maxViews - viewsCount).clamp(0, maxViews);

  /// Budget consommé en FCFA.
  int get budgetUsed => viewsCount * costPerView;

  /// Progression des vues (0.0 → 1.0).
  double get viewsProgress =>
      maxViews > 0 ? (viewsCount / maxViews).clamp(0.0, 1.0) : 0.0;

  /// Libellé français du mode de fin.
  String get endModeLabel => switch (endMode) {
        'date' => 'Fin : à une date précise',
        'target_reached' => 'Fin : quand le ciblage est atteint',
        'manual' => 'Fin : arrêt manuel',
        _ => 'Fin : quand le ciblage est atteint',
      };

  // ---------------------------------------------------------------------------
  // fromJson
  // ---------------------------------------------------------------------------

  factory CampaignModel.fromJson(Map<String, dynamic> json) {
    return CampaignModel(
      id: (json['id'] as num).toInt(),
      advertiserId: (json['advertiser_id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      format: CampaignFormat.fromString(json['format'] as String?),
      status: CampaignStatus.fromString(json['status'] as String?),
      budget: (json['budget'] as num?)?.toInt() ?? 0,
      costPerView: (json['cost_per_view'] as num?)?.toInt() ?? 0,
      maxViews: (json['max_views'] as num?)?.toInt() ?? 0,
      viewsCount: (json['views_count'] as num?)?.toInt() ?? 0,
      mediaUrl: json['media_url'] as String?,
      thumbnailUrl: json['thumbnail_url'] as String?,
      durationSeconds: (json['duration_seconds'] as num?)?.toInt(),
      targeting: json['targeting'] as Map<String, dynamic>?,
      startsAt: json['starts_at'] as String?,
      endsAt: json['ends_at'] as String?,
      approvedAt: json['approved_at'] as String?,
      approvedBy: (json['approved_by'] as num?)?.toInt(),
      rejectionReason: json['rejection_reason'] as String?,
      createdAt: json['created_at'] as String? ?? '',
      updatedAt: json['updated_at'] as String?,
      endMode: json['end_mode'] as String?,
    );
  }

  // ---------------------------------------------------------------------------
  // toJson (pour la création / modification)
  // ---------------------------------------------------------------------------

  Map<String, dynamic> toCreateJson() {
    return {
      'title': title,
      if (description != null) 'description': description,
      'format': format.name,
      'budget': budget,
      'cost_per_view': costPerView,
      if (targeting != null) 'targeting': targeting,
      if (durationSeconds != null) 'duration_seconds': durationSeconds,
      if (endMode != null) 'end_mode': endMode,
    };
  }

  CampaignModel copyWith({
    int? id,
    int? advertiserId,
    String? title,
    String? description,
    CampaignFormat? format,
    CampaignStatus? status,
    int? budget,
    int? costPerView,
    int? maxViews,
    int? viewsCount,
    String? mediaUrl,
    String? thumbnailUrl,
    int? durationSeconds,
    Map<String, dynamic>? targeting,
    String? startsAt,
    String? endsAt,
    String? approvedAt,
    int? approvedBy,
    String? rejectionReason,
    String? createdAt,
    String? updatedAt,
    String? endMode,
  }) {
    return CampaignModel(
      id: id ?? this.id,
      advertiserId: advertiserId ?? this.advertiserId,
      title: title ?? this.title,
      description: description ?? this.description,
      format: format ?? this.format,
      status: status ?? this.status,
      budget: budget ?? this.budget,
      costPerView: costPerView ?? this.costPerView,
      maxViews: maxViews ?? this.maxViews,
      viewsCount: viewsCount ?? this.viewsCount,
      mediaUrl: mediaUrl ?? this.mediaUrl,
      thumbnailUrl: thumbnailUrl ?? this.thumbnailUrl,
      durationSeconds: durationSeconds ?? this.durationSeconds,
      targeting: targeting ?? this.targeting,
      startsAt: startsAt ?? this.startsAt,
      endsAt: endsAt ?? this.endsAt,
      approvedAt: approvedAt ?? this.approvedAt,
      approvedBy: approvedBy ?? this.approvedBy,
      rejectionReason: rejectionReason ?? this.rejectionReason,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      endMode: endMode ?? this.endMode,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is CampaignModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}

// ---------------------------------------------------------------------------
// CampaignDetailModel — réponse enrichie de GET /campaigns/{id}
// ---------------------------------------------------------------------------

/// Détail d'une campagne avec statistiques.
class CampaignDetailModel {
  const CampaignDetailModel({
    required this.campaign,
    required this.viewsCount,
    required this.budgetUsed,
    required this.remainingViews,
  });

  final CampaignModel campaign;
  final int viewsCount;
  final int budgetUsed;
  final int remainingViews;

  factory CampaignDetailModel.fromJson(Map<String, dynamic> json) {
    final campaignData = json['campaign'] as Map<String, dynamic>? ?? json;
    return CampaignDetailModel(
      campaign: CampaignModel.fromJson(campaignData),
      viewsCount: (json['views_count'] as num?)?.toInt() ??
          (campaignData['views_count'] as num?)?.toInt() ??
          0,
      budgetUsed: (json['budget_used'] as num?)?.toInt() ?? 0,
      remainingViews: (json['remaining_views'] as num?)?.toInt() ?? 0,
    );
  }
}

