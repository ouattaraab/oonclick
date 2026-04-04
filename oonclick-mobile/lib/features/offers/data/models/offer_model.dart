/// Offre partenaire avec cashback.
class OfferModel {
  const OfferModel({
    required this.id,
    required this.partnerName,
    required this.cashbackPercent,
    this.description,
    this.logoUrl,
    this.promoCode,
    this.category,
    this.expiresAt,
  });

  final int id;
  final String partnerName;
  final double cashbackPercent;
  final String? description;
  final String? logoUrl;
  final String? promoCode;
  final String? category;
  final DateTime? expiresAt;

  bool get isExpired =>
      expiresAt != null && expiresAt!.isBefore(DateTime.now());

  factory OfferModel.fromJson(Map<String, dynamic> json) {
    return OfferModel(
      id: (json['id'] as num).toInt(),
      partnerName: json['partner_name'] as String? ?? '',
      cashbackPercent: (json['cashback_percent'] as num?)?.toDouble() ?? 0.0,
      description: json['description'] as String?,
      logoUrl: json['logo_url'] as String?,
      promoCode: json['promo_code'] as String?,
      category: json['category'] as String?,
      expiresAt: json['expires_at'] != null
          ? DateTime.tryParse(json['expires_at'] as String)
          : null,
    );
  }
}

/// Résultat de la soumission d'une demande de cashback.
class ClaimResult {
  const ClaimResult({
    required this.message,
    required this.claimId,
    required this.cashbackAmount,
    required this.status,
  });

  final String message;
  final int claimId;
  final int cashbackAmount;

  /// 'pending' | 'credited'
  final String status;

  bool get isCredited => status == 'credited';

  factory ClaimResult.fromJson(Map<String, dynamic> json) {
    return ClaimResult(
      message: json['message'] as String? ?? '',
      claimId: (json['claim_id'] as num?)?.toInt() ?? 0,
      cashbackAmount: (json['cashback_amount'] as num?)?.toInt() ?? 0,
      status: json['status'] as String? ?? 'pending',
    );
  }
}
