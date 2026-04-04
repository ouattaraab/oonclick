/// Profil de gamification d'un utilisateur.
class GamificationProfile {
  const GamificationProfile({
    required this.xp,
    required this.level,
    required this.nextLevel,
    required this.xpForNext,
    required this.progressPercent,
    required this.badges,
    required this.badgesCount,
  });

  /// Points d'expérience actuels.
  final int xp;

  /// Niveau actuel.
  final int level;

  /// Prochain niveau à atteindre.
  final int nextLevel;

  /// XP nécessaires pour atteindre [nextLevel].
  final int xpForNext;

  /// Progression entre le niveau actuel et le suivant (0.0 → 1.0).
  final double progressPercent;

  /// Liste de tous les badges (gagnés et non gagnés).
  final List<BadgeModel> badges;

  /// Nombre de badges gagnés.
  final int badgesCount;

  factory GamificationProfile.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    final badgeList = (data['badges'] as List<dynamic>?)
            ?.whereType<Map<String, dynamic>>()
            .map(BadgeModel.fromJson)
            .toList() ??
        [];
    final earned = badgeList.where((b) => b.earned).length;
    return GamificationProfile(
      xp: (data['xp'] as num?)?.toInt() ?? 0,
      level: (data['level'] as num?)?.toInt() ?? 1,
      nextLevel: (data['next_level'] as num?)?.toInt() ?? 2,
      xpForNext: (data['xp_for_next'] as num?)?.toInt() ?? 100,
      progressPercent:
          (data['progress_percent'] as num?)?.toDouble() ?? 0.0,
      badges: badgeList,
      badgesCount: earned,
    );
  }
}

/// Un badge de gamification.
class BadgeModel {
  const BadgeModel({
    required this.id,
    required this.name,
    required this.displayName,
    required this.description,
    required this.icon,
    required this.xpRequired,
    required this.level,
    required this.category,
    required this.earned,
  });

  final int id;

  /// Nom technique (snake_case).
  final String name;

  /// Nom affiché à l'utilisateur.
  final String displayName;

  final String description;

  /// Emoji ou nom d'icône.
  final String icon;

  /// XP nécessaires pour débloquer ce badge.
  final int xpRequired;

  /// Niveau de rareté du badge.
  final int level;

  /// Catégorie (ex: 'engagement', 'wallet', 'referral').
  final String category;

  /// L'utilisateur a-t-il gagné ce badge ?
  final bool earned;

  factory BadgeModel.fromJson(Map<String, dynamic> json) {
    return BadgeModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
      displayName: json['display_name'] as String? ??
          json['name'] as String? ??
          'Badge',
      description: json['description'] as String? ?? '',
      icon: json['icon'] as String? ?? '🏅',
      xpRequired: (json['xp_required'] as num?)?.toInt() ?? 0,
      level: (json['level'] as num?)?.toInt() ?? 1,
      category: json['category'] as String? ?? 'general',
      earned: (json['earned'] as bool?) ?? false,
    );
  }
}

/// Entrée du classement.
class LeaderboardEntry {
  const LeaderboardEntry({
    required this.rank,
    required this.userId,
    required this.name,
    required this.xp,
    required this.level,
    this.phone,
  });

  final int rank;
  final int userId;
  final String name;
  final int xp;
  final int level;

  /// Numéro de téléphone masqué (optionnel).
  final String? phone;

  factory LeaderboardEntry.fromJson(Map<String, dynamic> json) {
    return LeaderboardEntry(
      rank: (json['rank'] as num?)?.toInt() ?? 0,
      userId: (json['user_id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? 'Utilisateur',
      xp: (json['xp'] as num?)?.toInt() ?? 0,
      level: (json['level'] as num?)?.toInt() ?? 1,
      phone: json['phone'] as String?,
    );
  }
}
