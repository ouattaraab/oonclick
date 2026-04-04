/// Represents a single advertisment campaign in the feed.
///
/// Formats: `video` | `scratch` | `quiz` | `flash`
class CampaignModel {
  const CampaignModel({
    required this.id,
    required this.title,
    required this.format,
    required this.mediaUrl,
    required this.durationSeconds,
    required this.amount,
    required this.formatMultiplier,
    this.thumbnailUrl,
    this.quizData,
  });

  final int id;
  final String title;

  /// `video` | `scratch` | `quiz` | `flash`
  final String format;

  /// Pre-signed URL for the video/media asset.
  final String mediaUrl;

  final String? thumbnailUrl;

  /// Duration of the ad in seconds; must be watched to at least 80%.
  final int durationSeconds;

  /// FCFA amount credited on completion.
  final int amount;

  /// Multiplier applied by the format (e.g. 1.5 for quiz).
  final double formatMultiplier;

  /// Optional list of quiz questions provided by the campaign backend.
  /// Each entry is a map with keys 'question' (String) and 'answers' (List<String>).
  /// When null, the quiz overlay falls back to built-in engagement questions.
  final List<Map<String, dynamic>>? quizData;

  // ---------------------------------------------------------------------------
  // Derived helpers
  // ---------------------------------------------------------------------------

  bool get isVideo => format == 'video';
  bool get isPhoto => format == 'photo';
  bool get isScratch => format == 'scratch';
  bool get isQuiz => format == 'quiz';
  bool get isFlash => format == 'flash';

  /// Whether this campaign uses an image instead of a video.
  bool get isImageBased => isPhoto || isFlash;

  /// Minimum seconds that must be watched before crediting (80% rule).
  int get minWatchSeconds => (durationSeconds * 0.8).ceil();

  // ---------------------------------------------------------------------------
  // Serialisation
  // ---------------------------------------------------------------------------

  factory CampaignModel.fromJson(Map<String, dynamic> json) {
    List<Map<String, dynamic>>? quizData;
    final rawQuiz = json['quiz_data'];
    if (rawQuiz is List && rawQuiz.isNotEmpty) {
      quizData = rawQuiz
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    }

    return CampaignModel(
      id: (json['id'] as num).toInt(),
      title: json['title'] as String,
      format: json['format'] as String? ?? 'video',
      mediaUrl: json['media_url'] as String,
      thumbnailUrl: json['thumbnail_url'] as String?,
      durationSeconds: (json['duration_seconds'] as num?)?.toInt() ?? 30,
      amount: (json['amount'] as num?)?.toInt() ?? 0,
      formatMultiplier:
          (json['format_multiplier'] as num?)?.toDouble() ?? 1.0,
      quizData: quizData,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'format': format,
      'media_url': mediaUrl,
      'thumbnail_url': thumbnailUrl,
      'duration_seconds': durationSeconds,
      'amount': amount,
      'format_multiplier': formatMultiplier,
      if (quizData != null) 'quiz_data': quizData,
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is CampaignModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    final quizSuffix =
        (quizData != null && quizData!.isNotEmpty) ? ', quiz:${quizData!.length}q' : '';
    return 'CampaignModel(id: $id, format: $format, amount: $amount FCFA$quizSuffix)';
  }
}
