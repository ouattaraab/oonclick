/// Coupon partenaire.
class CouponModel {
  const CouponModel({
    required this.id,
    required this.code,
    required this.discountType,
    required this.discountValue,
    required this.partnerName,
    required this.discountLabel,
    required this.isActive,
    this.description,
    this.expiresAt,
  });

  final int id;
  final String code;

  /// 'percent' | 'fixed'
  final String discountType;
  final int discountValue;
  final String partnerName;

  /// Libellé calculé côté serveur : "-10%" ou "-500 FCFA"
  final String discountLabel;
  final bool isActive;
  final String? description;
  final DateTime? expiresAt;

  bool get isExpired =>
      expiresAt != null && expiresAt!.isBefore(DateTime.now());

  bool get isPercent => discountType == 'percent';

  factory CouponModel.fromJson(Map<String, dynamic> json) {
    return CouponModel(
      id: (json['id'] as num).toInt(),
      code: json['code'] as String? ?? '',
      discountType: json['discount_type'] as String? ?? 'percent',
      discountValue: (json['discount_value'] as num?)?.toInt() ?? 0,
      partnerName: json['partner_name'] as String? ?? '',
      discountLabel: json['discount_label'] as String? ?? '',
      isActive: json['is_active'] as bool? ?? true,
      description: json['description'] as String?,
      expiresAt: json['expires_at'] != null
          ? DateTime.tryParse(json['expires_at'] as String)
          : null,
    );
  }
}

/// Coupon collecté par un utilisateur (avec la relation coupon).
class UserCouponModel {
  const UserCouponModel({
    required this.id,
    required this.isUsed,
    required this.collectedAt,
    this.coupon,
    this.usedAt,
  });

  final int id;
  final bool isUsed;
  final DateTime collectedAt;
  final CouponModel? coupon;
  final DateTime? usedAt;

  bool get isAvailable =>
      !isUsed && (coupon?.isActive ?? false) && !(coupon?.isExpired ?? false);

  factory UserCouponModel.fromJson(Map<String, dynamic> json) {
    return UserCouponModel(
      id: (json['id'] as num).toInt(),
      isUsed: json['is_used'] as bool? ?? false,
      collectedAt: DateTime.tryParse(json['collected_at'] as String? ?? '') ??
          DateTime.now(),
      usedAt: json['used_at'] != null
          ? DateTime.tryParse(json['used_at'] as String)
          : null,
      coupon: json['coupon'] != null
          ? CouponModel.fromJson(json['coupon'] as Map<String, dynamic>)
          : null,
    );
  }
}
