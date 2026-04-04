/// Question d'un sondage.
class SurveyQuestion {
  const SurveyQuestion({
    required this.type,
    required this.text,
    this.options,
    required this.required,
  });

  /// Type : 'text', 'radio', 'checkbox'
  final String type;

  /// Texte de la question.
  final String text;

  /// Options disponibles (null pour le type 'text').
  final List<String>? options;

  /// La question est-elle obligatoire ?
  final bool required;

  factory SurveyQuestion.fromJson(Map<String, dynamic> json) {
    return SurveyQuestion(
      type: json['type'] as String? ?? 'text',
      text: json['text'] as String? ?? '',
      options: (json['options'] as List<dynamic>?)
          ?.map((e) => e.toString())
          .toList(),
      required: json['required'] as bool? ?? true,
    );
  }
}

/// Sondage rémunéré disponible pour l'abonné.
class SurveyModel {
  const SurveyModel({
    required this.id,
    required this.title,
    this.description,
    required this.rewardAmount,
    required this.rewardXp,
    required this.questions,
    required this.responsesCount,
    this.maxResponses,
    this.expiresAt,
  });

  final int id;
  final String title;
  final String? description;

  /// Récompense en FCFA.
  final int rewardAmount;

  /// Points XP attribués à la complétion.
  final int rewardXp;

  /// Liste des questions du sondage.
  final List<SurveyQuestion> questions;

  /// Nombre de réponses déjà enregistrées.
  final int responsesCount;

  /// Quota maximum (null = illimité).
  final int? maxResponses;

  /// Date d'expiration (null = pas d'expiration).
  final DateTime? expiresAt;

  factory SurveyModel.fromJson(Map<String, dynamic> json) {
    return SurveyModel(
      id: (json['id'] as num).toInt(),
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      rewardAmount: (json['reward_amount'] as num?)?.toInt() ?? 0,
      rewardXp: (json['reward_xp'] as num?)?.toInt() ?? 0,
      questions: (json['questions'] as List<dynamic>?)
              ?.map((q) => SurveyQuestion.fromJson(q as Map<String, dynamic>))
              .toList() ??
          [],
      responsesCount: (json['responses_count'] as num?)?.toInt() ?? 0,
      maxResponses: (json['max_responses'] as num?)?.toInt(),
      expiresAt: json['expires_at'] != null
          ? DateTime.tryParse(json['expires_at'] as String)
          : null,
    );
  }
}

/// Résultat après soumission d'un sondage.
class SurveySubmitResult {
  const SurveySubmitResult({
    required this.message,
    required this.reward,
    required this.xp,
  });

  final String message;
  final int reward;
  final int xp;

  factory SurveySubmitResult.fromJson(Map<String, dynamic> json) {
    return SurveySubmitResult(
      message: json['message'] as String? ?? 'Merci pour votre participation !',
      reward: (json['reward'] as num?)?.toInt() ?? 0,
      xp: (json['xp'] as num?)?.toInt() ?? 0,
    );
  }
}
