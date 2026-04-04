/// Modèle d'un retrait effectué par l'utilisateur.
class WithdrawalModel {
  const WithdrawalModel({
    required this.id,
    required this.amount,
    required this.mobileOperator,
    required this.mobilePhone,
    required this.status,
    required this.createdAt,
    this.processedAt,
    this.failureReason,
    this.reference,
  });

  final int id;

  /// Montant en FCFA.
  final int amount;

  /// Opérateur : 'MTN' | 'Moov' | 'Orange'.
  final String mobileOperator;

  /// Numéro mobile destinataire.
  final String mobilePhone;

  /// Statut : 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'.
  final String status;

  /// Date ISO-8601 de création.
  final String createdAt;

  /// Date ISO-8601 de traitement (null si toujours en attente).
  final String? processedAt;

  /// Raison d'échec si [status] == 'failed'.
  final String? failureReason;

  /// Référence de transaction (fournie par l'opérateur).
  final String? reference;

  // ---------------------------------------------------------------------------
  // Derived helpers
  // ---------------------------------------------------------------------------

  bool get isPending => status == 'pending';
  bool get isProcessing => status == 'processing';
  bool get isCompleted => status == 'completed';
  bool get isFailed => status == 'failed';
  bool get isCancelled => status == 'cancelled';
  bool get canBeCancelled => isPending;

  String get statusLabel => switch (status) {
        'pending' => 'En attente',
        'processing' => 'En cours',
        'completed' => 'Complété',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
        _ => 'Inconnu',
      };

  factory WithdrawalModel.fromJson(Map<String, dynamic> json) {
    return WithdrawalModel(
      id: (json['id'] as num).toInt(),
      amount: (json['amount'] as num?)?.toInt() ?? 0,
      mobileOperator: json['mobile_operator'] as String? ?? '',
      mobilePhone: json['mobile_phone'] as String? ??
          json['phone'] as String? ??
          '',
      status: json['status'] as String? ?? 'pending',
      createdAt: json['created_at'] as String? ?? '',
      processedAt: json['processed_at'] as String?,
      failureReason: json['failure_reason'] as String?,
      reference: json['reference'] as String?,
    );
  }
}
