/// Statut de check-in de l'utilisateur.
class CheckinStatusModel {
  const CheckinStatusModel({
    required this.checkedInToday,
    required this.currentStreak,
    this.lastCheckinDate,
    required this.totalCheckins,
    required this.totalBonusEarned,
    this.bonusForToday = 0,
    this.checkinHistory = const [],
  });

  /// L'utilisateur a-t-il déjà fait son check-in aujourd'hui ?
  final bool checkedInToday;

  /// Streak actuel en jours consécutifs.
  final int currentStreak;

  /// Date ISO-8601 du dernier check-in (peut être null).
  final String? lastCheckinDate;

  /// Nombre total de check-ins depuis la création du compte.
  final int totalCheckins;

  /// Total des bonus FCFA gagnés via les check-ins.
  final int totalBonusEarned;

  /// Bonus prévu si l'utilisateur fait son check-in maintenant.
  final int bonusForToday;

  /// Historique des 7 derniers jours (liste de dates ISO-8601).
  final List<String> checkinHistory;

  factory CheckinStatusModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    final history = (data['checkin_history'] as List<dynamic>?)
            ?.map((e) => e.toString())
            .toList() ??
        [];
    return CheckinStatusModel(
      checkedInToday: (data['checked_in_today'] as bool?) ?? false,
      currentStreak: (data['current_streak'] as num?)?.toInt() ?? 0,
      lastCheckinDate: data['last_checkin_date'] as String?,
      totalCheckins: (data['total_checkins'] as num?)?.toInt() ?? 0,
      totalBonusEarned: (data['total_bonus_earned'] as num?)?.toInt() ?? 0,
      bonusForToday: (data['bonus_for_today'] as num?)?.toInt() ?? 0,
      checkinHistory: history,
    );
  }
}

/// Résultat d'un check-in réussi.
class CheckinResultModel {
  const CheckinResultModel({
    required this.streakDay,
    required this.bonusAmount,
    required this.newBalance,
    required this.message,
  });

  /// Numéro du jour dans le streak courant.
  final int streakDay;

  /// Bonus FCFA crédité pour ce check-in.
  final int bonusAmount;

  /// Nouveau solde du wallet après crédit du bonus.
  final int newBalance;

  /// Message de félicitation retourné par l'API.
  final String message;

  factory CheckinResultModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    return CheckinResultModel(
      streakDay: (data['streak_day'] as num?)?.toInt() ?? 1,
      bonusAmount: (data['bonus_amount'] as num?)?.toInt() ?? 0,
      newBalance: (data['new_balance'] as num?)?.toInt() ?? 0,
      message: data['message'] as String? ?? 'Check-in effectué !',
    );
  }
}
