/// Statut global KYC de l'utilisateur.
class KycStatusModel {
  const KycStatusModel({
    required this.kycLevel,
    required this.levels,
    required this.overallStatus,
  });

  /// Niveau KYC actuel (0, 1, 2, ou 3).
  final int kycLevel;

  /// Détails par niveau : { "1": { "status": "approved", ... }, ... }
  final Map<String, dynamic> levels;

  /// Statut global : 'none' | 'pending' | 'approved' | 'rejected'.
  final String overallStatus;

  factory KycStatusModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    return KycStatusModel(
      kycLevel: (data['kyc_level'] as num?)?.toInt() ?? 0,
      levels: data['levels'] as Map<String, dynamic>? ?? {},
      overallStatus: data['overall_status'] as String? ?? 'none',
    );
  }
}

/// Un document KYC soumis par l'utilisateur.
class KycDocumentModel {
  const KycDocumentModel({
    required this.id,
    required this.level,
    required this.documentType,
    required this.status,
    this.rejectionReason,
    required this.submittedAt,
  });

  final int id;

  /// Niveau KYC auquel appartient ce document (1, 2, ou 3).
  final int level;

  /// Type de document (ex: 'national_id', 'selfie', 'business_registration').
  final String documentType;

  /// Statut : 'pending' | 'approved' | 'rejected'.
  final String status;

  /// Raison du refus, uniquement si [status] == 'rejected'.
  final String? rejectionReason;

  /// Date ISO-8601 de soumission.
  final String submittedAt;

  // ---------------------------------------------------------------------------
  // Derived helpers
  // ---------------------------------------------------------------------------

  bool get isPending => status == 'pending';
  bool get isApproved => status == 'approved';
  bool get isRejected => status == 'rejected';

  String get statusLabel => switch (status) {
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
        'pending' => 'En cours',
        _ => 'Inconnu',
      };

  String get documentLabel => switch (documentType) {
        'national_id' => 'Pièce d\'identité nationale',
        'selfie' => 'Selfie avec pièce d\'identité',
        'business_registration' => 'Registre de commerce',
        'tax_certificate' => 'Attestation fiscale',
        _ => documentType,
      };

  factory KycDocumentModel.fromJson(Map<String, dynamic> json) {
    return KycDocumentModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      level: (json['level'] as num?)?.toInt() ?? 1,
      documentType: json['document_type'] as String? ?? '',
      status: json['status'] as String? ?? 'pending',
      rejectionReason: json['rejection_reason'] as String?,
      submittedAt: json['submitted_at'] as String? ??
          json['created_at'] as String? ??
          '',
    );
  }
}
