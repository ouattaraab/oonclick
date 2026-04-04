import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/survey_model.dart';
import '../../data/repositories/survey_repository.dart';

// ---------------------------------------------------------------------------
// Liste des sondages disponibles
// ---------------------------------------------------------------------------

/// Charge et expose la liste des sondages disponibles pour l'utilisateur.
final surveysProvider =
    AsyncNotifierProvider<SurveysNotifier, List<SurveyModel>>(
        SurveysNotifier.new);

class SurveysNotifier extends AsyncNotifier<List<SurveyModel>> {
  @override
  Future<List<SurveyModel>> build() async {
    return ref.read(surveyRepositoryProvider).getSurveys();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(surveyRepositoryProvider).getSurveys(),
    );
  }
}

// ---------------------------------------------------------------------------
// Détail d'un sondage
// ---------------------------------------------------------------------------

/// Charge les détails (et questions) d'un sondage identifié par son [id].
final surveyDetailProvider =
    AsyncNotifierProviderFamily<SurveyDetailNotifier, SurveyModel, int>(
        SurveyDetailNotifier.new);

class SurveyDetailNotifier
    extends FamilyAsyncNotifier<SurveyModel, int> {
  @override
  Future<SurveyModel> build(int arg) async {
    return ref.read(surveyRepositoryProvider).getSurvey(arg);
  }
}
