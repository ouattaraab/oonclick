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
    // Le serveur peut retourner les stats sous une clé `wallet` ou à la racine.
    final wallet = json['wallet'] as Map<String, dynamic>? ?? {};
    final profile = json['profile'] as Map<String, dynamic>? ?? {};

    return ProfileStatsModel(
      totalViews: (json['total_views'] as num?)?.toInt() ??
          (profile['total_views'] as num?)?.toInt() ?? 0,
      totalEarned: (wallet['total_earned'] as num?)?.toInt() ??
          (json['total_earned'] as num?)?.toInt() ?? 0,
      totalWithdrawn: (wallet['total_withdrawn'] as num?)?.toInt() ??
          (json['total_withdrawn'] as num?)?.toInt() ?? 0,
      currentBalance: (wallet['balance'] as num?)?.toInt() ??
          (json['balance'] as num?)?.toInt() ?? 0,
      trustScore: (json['trust_score'] as num?)?.toInt() ?? 0,
      kycLevel: (json['kyc_level'] as num?)?.toInt() ?? 0,
      referralCode: json['referral_code'] as String? ??
          profile['referral_code'] as String? ?? '',
      referralCount: (json['referral_count'] as num?)?.toInt() ??
          (profile['referral_count'] as num?)?.toInt() ?? 0,
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
    );
  }

  @override
  String toString() =>
      'ProfileStatsModel(trustScore: $trustScore, kycLevel: $kycLevel, '
      'balance: $currentBalance, referralCode: $referralCode)';
}
