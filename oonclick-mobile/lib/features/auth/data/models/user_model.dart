import 'dart:convert';

/// Represents an authenticated oon.click user.
///
/// Roles: `subscriber` | `advertiser` | `admin`
/// KYC levels: 0 (none) → 3 (fully verified)
class UserModel {
  const UserModel({
    required this.id,
    this.phone,
    required this.role,
    required this.kycLevel,
    required this.trustScore,
    required this.isActive,
    required this.isSuspended,
    this.name,
    this.email,
    this.phoneVerifiedAt,
  });

  final int id;
  final String? name;
  final String? phone;
  final String? email;

  /// `subscriber` | `advertiser` | `admin`
  final String role;

  final int kycLevel;
  final int trustScore;
  final bool isActive;
  final bool isSuspended;

  /// ISO-8601 timestamp, or `null` if phone not yet verified.
  final String? phoneVerifiedAt;

  // ---------------------------------------------------------------------------
  // Derived helpers
  // ---------------------------------------------------------------------------

  bool get isSubscriber => role == 'subscriber';
  bool get isAdvertiser => role == 'advertiser';
  bool get isAdmin => role == 'admin';
  bool get phoneVerified => phoneVerifiedAt != null;

  // ---------------------------------------------------------------------------
  // Serialisation
  // ---------------------------------------------------------------------------

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String?,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      role: json['role'] as String? ?? 'subscriber',
      kycLevel: (json['kyc_level'] as num?)?.toInt() ?? 0,
      trustScore: (json['trust_score'] as num?)?.toInt() ?? 0,
      isActive: (json['is_active'] as bool?) ?? true,
      isSuspended: (json['is_suspended'] as bool?) ?? false,
      phoneVerifiedAt: json['phone_verified_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'email': email,
      'role': role,
      'kyc_level': kycLevel,
      'trust_score': trustScore,
      'is_active': isActive,
      'is_suspended': isSuspended,
      'phone_verified_at': phoneVerifiedAt,
    };
  }

  /// Convenience: encode to a JSON string for secure storage.
  String toJsonString() => jsonEncode(toJson());

  factory UserModel.fromJsonString(String jsonString) =>
      UserModel.fromJson(jsonDecode(jsonString) as Map<String, dynamic>);

  // ---------------------------------------------------------------------------
  // CopyWith
  // ---------------------------------------------------------------------------

  UserModel copyWith({
    int? id,
    String? name,
    String? phone,
    String? email,
    String? role,
    int? kycLevel,
    int? trustScore,
    bool? isActive,
    bool? isSuspended,
    String? phoneVerifiedAt,
  }) {
    return UserModel(
      id: id ?? this.id,
      name: name ?? this.name,
      phone: phone ?? this.phone,
      email: email ?? this.email,
      role: role ?? this.role,
      kycLevel: kycLevel ?? this.kycLevel,
      trustScore: trustScore ?? this.trustScore,
      isActive: isActive ?? this.isActive,
      isSuspended: isSuspended ?? this.isSuspended,
      phoneVerifiedAt: phoneVerifiedAt ?? this.phoneVerifiedAt,
    );
  }

  @override
  String toString() => 'UserModel(id: $id, phone: $phone, role: $role)';

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is UserModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
