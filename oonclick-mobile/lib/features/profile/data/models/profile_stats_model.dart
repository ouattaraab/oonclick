/// Statistiques enrichies du profil retournées par GET /auth/me.
///
/// Le serveur inclut ces champs dans la réponse étendue du profil utilisateur.
class ProfileStatsModel {
  const ProfileStatsModel({
    required this.totalViews,
    required this.totalEarned,
    required this.totalWithdrawn,
    required this.currentBalance,
    required this.trustScore,
    required this.kycLevel,
    required this.referralCode,
    required this.referralCount,
    this.city,
  });

  /// Nombre total de publicités regardées jusqu'au bout.
  final int totalViews;

  /// Montant total crédité en FCFA depuis la création du compte.
  final int totalEarned;

  /// Montant total retiré en FCFA depuis la création du compte.
  final int totalWithdrawn;

  /// Solde disponible actuel en FCFA.
  final int currentBalance;

  /// Score de confiance de 0 à 100.
  final int trustScore;

  /// Niveau KYC de 0 (aucun) à 3 (pleinement vérifié).
  final int kycLevel;

  /// Code de parrainage unique de l'utilisateur.
  final String referralCode;

  /// Nombre d'amis parrainés via ce code.
  final int referralCount;

  /// Ville de résidence de l'utilisateur (depuis le profil).
  final String? city;

  // ---------------------------------------------------------------------------
  // Seuils KYC
  // ---------------------------------------------------------------------------

  /// Libellé du niveau KYC courant.
  String get kycLevelLabel {
    return switch (kycLevel) {
      0 => 'Non vérifié',
      1 => 'KYC Niveau 1',
      2 => 'KYC Niveau 2',
      3 => 'KYC Niveau 3',
      _ => 'Inconnu',
    };
  }

  /// Limite de retrait autorisée selon le niveau KYC (en FCFA).
  String get kycWithdrawalLimit {
    return switch (kycLevel) {
      0 => 'Aucun retrait autorisé',
      1 => 'Retrait jusqu\'à 10 000 FCFA',
      2 => 'Retrait jusqu\'à 50 000 FCFA',
      3 => 'Retrait illimité',
      _ => '',
    };
  }

  // ---------------------------------------------------------------------------
  // Sérialisation
  // ---------------------------------------------------------------------------

  factory ProfileStatsModel.fromJson(Map<String, dynamic> json) {
    // Backend /auth/me returns { "user": {...}, "profile": {...}, "wallet": {...} }
    final user    = json['user']    as Map<String, dynamic>? ?? json;
    final wallet  = json['wallet']  as Map<String, dynamic>? ?? {};
    final profile = json['profile'] as Map<String, dynamic>? ?? {};

    return ProfileStatsModel(
      totalViews: (json['total_views'] as num?)?.toInt() ??
          (user['total_views'] as num?)?.toInt() ??
          (profile['total_views'] as num?)?.toInt() ?? 0,
      totalEarned: (wallet['total_earned'] as num?)?.toInt() ??
          (json['total_earned'] as num?)?.toInt() ?? 0,
      totalWithdrawn: (wallet['total_withdrawn'] as num?)?.toInt() ??
          (json['total_withdrawn'] as num?)?.toInt() ?? 0,
      currentBalance: (wallet['balance'] as num?)?.toInt() ??
          (json['balance'] as num?)?.toInt() ?? 0,
      trustScore: (user['trust_score'] as num?)?.toInt() ??
          (json['trust_score'] as num?)?.toInt() ?? 0,
      kycLevel: (user['kyc_level'] as num?)?.toInt() ??
          (json['kyc_level'] as num?)?.toInt() ?? 0,
      referralCode: profile['referral_code'] as String? ??
          json['referral_code'] as String? ?? '',
      referralCount: (profile['referral_count'] as num?)?.toInt() ??
          (json['referral_count'] as num?)?.toInt() ?? 0,
      city: profile['city'] as String? ?? json['city'] as String?,
    );
  }

  // ---------------------------------------------------------------------------
  // CopyWith
  // ---------------------------------------------------------------------------

  ProfileStatsModel copyWith({
    int? totalViews,
    int? totalEarned,
    int? totalWithdrawn,
    int? currentBalance,
    int? trustScore,
    int? kycLevel,
    String? referralCode,
    int? referralCount,
    String? city,
  }) {
    return ProfileStatsModel(
      totalViews: totalViews ?? this.totalViews,
      totalEarned: totalEarned ?? this.totalEarned,
      totalWithdrawn: totalWithdrawn ?? this.totalWithdrawn,
      currentBalance: currentBalance ?? this.currentBalance,
      trustScore: trustScore ?? this.trustScore,
      kycLevel: kycLevel ?? this.kycLevel,
      referralCode: referralCode ?? this.referralCode,
      referralCount: referralCount ?? this.referralCount,
      city: city ?? this.city,
    );
  }

  @override
  String toString() =>
      'ProfileStatsModel(trustScore: $trustScore, kycLevel: $kycLevel, '
      'balance: $currentBalance, referralCode: $referralCode)';
}
