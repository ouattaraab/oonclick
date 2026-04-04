/// Mission quotidienne de l'utilisateur.
class MissionModel {
  const MissionModel({
    required this.id,
    required this.slug,
    required this.title,
    required this.type,
    this.description,
    required this.target,
    required this.currentProgress,
    required this.rewardFcfa,
    required this.rewardXp,
    required this.completed,
    required this.rewarded,
    this.icon,
  });

  /// ID de l'enregistrement UserMission en base.
  final int id;

  /// Identifiant unique de la mission (ex: 'watch_3_ads').
  final String slug;

  /// Titre affiché dans l'interface.
  final String title;

  /// Type de mission (views, checkin, referral, survey, …).
  final String type;

  /// Description facultative.
  final String? description;

  /// Objectif à atteindre.
  final int target;

  /// Progression actuelle de l'utilisateur.
  final int currentProgress;

  /// Récompense FCFA à réclamer.
  final int rewardFcfa;

  /// Récompense XP à réclamer.
  final int rewardXp;

  /// La mission est-elle complétée ?
  final bool completed;

  /// La récompense a-t-elle été réclamée ?
  final bool rewarded;

  /// Icône optionnelle (nom d'icône Material).
  final String? icon;

  /// Pourcentage de progression [0.0, 1.0].
  double get progressPercent =>
      target > 0 ? (currentProgress / target).clamp(0.0, 1.0) : 0.0;

  factory MissionModel.fromJson(Map<String, dynamic> json) {
    return MissionModel(
      id: (json['id'] as num).toInt(),
      slug: json['slug'] as String? ?? '',
      title: json['title'] as String? ?? '',
      type: json['type'] as String? ?? '',
      description: json['description'] as String?,
      target: (json['target'] as num?)?.toInt() ?? 1,
      currentProgress: (json['current_progress'] as num?)?.toInt() ?? 0,
      rewardFcfa: (json['reward_fcfa'] as num?)?.toInt() ?? 0,
      rewardXp: (json['reward_xp'] as num?)?.toInt() ?? 0,
      completed: json['completed'] as bool? ?? false,
      rewarded: json['rewarded'] as bool? ?? false,
      icon: json['icon'] as String?,
    );
  }
}

/// Résultat de la réclamation d'une récompense de mission.
class MissionClaimResult {
  const MissionClaimResult({
    required this.message,
    required this.rewardFcfa,
    required this.rewardXp,
    required this.missionTitle,
  });

  final String message;
  final int rewardFcfa;
  final int rewardXp;
  final String missionTitle;

  factory MissionClaimResult.fromJson(Map<String, dynamic> json) {
    return MissionClaimResult(
      message: json['message'] as String? ?? 'Récompense réclamée !',
      rewardFcfa: (json['reward_fcfa'] as num?)?.toInt() ?? 0,
      rewardXp: (json['reward_xp'] as num?)?.toInt() ?? 0,
      missionTitle: json['mission'] as String? ?? '',
    );
  }
}
