/// Modèle d'une notification in-app (Laravel Notifications).
///
/// Le champ [type] correspond au FQCN de la notification Laravel,
/// par exemple `App\Notifications\CreditReceivedNotification`.
class NotificationModel {
  const NotificationModel({
    required this.id,
    required this.type,
    required this.data,
    required this.createdAt,
    this.readAt,
  });

  /// UUID de la notification.
  final String id;

  /// FQCN Laravel, ex : `App\Notifications\CreditReceivedNotification`.
  final String type;

  /// Données contextuelles de la notification.
  final NotificationData data;

  /// Timestamp ISO-8601 de lecture, `null` si non lue.
  final String? readAt;

  /// Timestamp ISO-8601 de création.
  final String createdAt;

  // ---------------------------------------------------------------------------
  // Helpers dérivés
  // ---------------------------------------------------------------------------

  /// `true` si la notification a été lue.
  bool get isRead => readAt != null;

  /// Nom court du type (après le dernier `\`).
  String get shortType {
    final parts = type.split(r'\');
    return parts.last;
  }

  // ---------------------------------------------------------------------------
  // Sérialisation
  // ---------------------------------------------------------------------------

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    // Laravel renvoie les données sous la clé `data` (objet ou chaîne JSON).
    final rawData = json['data'];
    final dataMap = rawData is Map<String, dynamic>
        ? rawData
        : <String, dynamic>{};

    return NotificationModel(
      id: json['id'] as String,
      type: json['type'] as String? ?? '',
      data: NotificationData.fromJson(dataMap),
      readAt: json['read_at'] as String?,
      createdAt: json['created_at'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type,
      'data': data.toJson(),
      'read_at': readAt,
      'created_at': createdAt,
    };
  }

  /// Retourne une copie marquée comme lue à [timestamp].
  NotificationModel markRead(String timestamp) {
    return NotificationModel(
      id: id,
      type: type,
      data: data,
      readAt: timestamp,
      createdAt: createdAt,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is NotificationModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() => 'NotificationModel(id: $id, type: $shortType, read: $isRead)';
}

// ---------------------------------------------------------------------------
// Données contextuelles
// ---------------------------------------------------------------------------

/// Payload embarqué dans une notification.
class NotificationData {
  const NotificationData({
    required this.title,
    required this.body,
    this.amount,
    this.campaignId,
    this.status,
  });

  /// Titre affiché en gras dans la liste.
  final String title;

  /// Corps du message.
  final String body;

  /// Montant en FCFA (présent pour les notifications de crédit).
  final String? amount;

  /// Identifiant de la campagne (présent pour les notifications de campagne).
  final int? campaignId;

  /// Statut de l'opération (présent pour les notifications de retrait).
  final String? status;

  // ---------------------------------------------------------------------------
  // Sérialisation
  // ---------------------------------------------------------------------------

  factory NotificationData.fromJson(Map<String, dynamic> json) {
    return NotificationData(
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ??
          json['message'] as String? ?? '',
      amount: json['amount']?.toString(),
      campaignId: (json['campaign_id'] as num?)?.toInt(),
      status: json['status'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'title': title,
      'body': body,
      if (amount != null) 'amount': amount,
      if (campaignId != null) 'campaign_id': campaignId,
      if (status != null) 'status': status,
    };
  }

  @override
  String toString() => 'NotificationData(title: $title)';
}
