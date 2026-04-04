/// Un filleul dans l'arbre de parrainage.
class ReferralEntry {
  const ReferralEntry({
    required this.userId,
    required this.name,
    required this.joinedAt,
    this.referredBy,
  });

  final int userId;
  final String name;
  final DateTime? joinedAt;

  /// Uniquement présent pour les filleuls de niveau 2 (id du parrain de niveau 1).
  final int? referredBy;

  factory ReferralEntry.fromJson(Map<String, dynamic> json) {
    return ReferralEntry(
      userId: (json['user_id'] as num).toInt(),
      name: json['name'] as String? ?? 'Inconnu',
      joinedAt: json['joined_at'] != null
          ? DateTime.tryParse(json['joined_at'] as String)
          : null,
      referredBy: json['referred_by'] != null
          ? (json['referred_by'] as num).toInt()
          : null,
    );
  }
}

/// Données d'un niveau de l'arbre de parrainage.
class ReferralLevel {
  const ReferralLevel({
    required this.referrals,
    required this.count,
    required this.earnings,
  });

  final List<ReferralEntry> referrals;
  final int count;

  /// Gains cumulés en FCFA pour ce niveau.
  final int earnings;

  factory ReferralLevel.fromJson(Map<String, dynamic> json) {
    final list = (json['referrals'] as List<dynamic>? ?? [])
        .map((e) => ReferralEntry.fromJson(e as Map<String, dynamic>))
        .toList();

    return ReferralLevel(
      referrals: list,
      count: (json['count'] as num?)?.toInt() ?? list.length,
      earnings: (json['earnings'] as num?)?.toInt() ?? 0,
    );
  }
}

/// Arbre de parrainage complet retourné par GET /referrals/tree.
class ReferralTreeModel {
  const ReferralTreeModel({
    required this.level1,
    required this.level2,
    required this.multiLevelEnabled,
    required this.config,
  });

  final ReferralLevel level1;
  final ReferralLevel level2;

  /// Indique si la feature `referral_levels` est activée côté serveur.
  final bool multiLevelEnabled;

  /// Configuration brute de la feature (bonus montants, etc.).
  final Map<String, dynamic> config;

  /// Total des gains des deux niveaux en FCFA.
  int get totalEarnings => level1.earnings + level2.earnings;

  factory ReferralTreeModel.fromJson(Map<String, dynamic> json) {
    return ReferralTreeModel(
      level1: ReferralLevel.fromJson(
          json['level_1'] as Map<String, dynamic>? ?? {}),
      level2: ReferralLevel.fromJson(
          json['level_2'] as Map<String, dynamic>? ?? {}),
      multiLevelEnabled: json['multi_level_enabled'] as bool? ?? false,
      config: json['config'] as Map<String, dynamic>? ?? {},
    );
  }
}
