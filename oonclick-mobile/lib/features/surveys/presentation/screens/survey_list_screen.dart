import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/config/feature_settings_provider.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/survey_model.dart';
import '../providers/survey_provider.dart';

/// Écran listant les sondages rémunérés disponibles.
class SurveyListScreen extends ConsumerWidget {
  const SurveyListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isEnabled = ref.watch(isFeatureEnabledProvider('surveys'));

    if (!isEnabled) {
      return Scaffold(
        backgroundColor: AppColors.bg,
        body: _TopBar(),
      );
    }

    final surveysAsync = ref.watch(surveysProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _TopBar(),
          Expanded(
            child: surveysAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (err, _) => _ErrorView(
                message: err.toString(),
                onRetry: () => ref.read(surveysProvider.notifier).refresh(),
              ),
              data: (surveys) => surveys.isEmpty
                  ? _EmptyView()
                  : RefreshIndicator(
                      color: AppColors.sky,
                      onRefresh: () =>
                          ref.read(surveysProvider.notifier).refresh(),
                      child: GridView.builder(
                        padding: const EdgeInsets.all(16),
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 1,
                          mainAxisExtent: 148,
                          mainAxisSpacing: 12,
                        ),
                        itemCount: surveys.length,
                        itemBuilder: (context, index) =>
                            _SurveyCard(survey: surveys[index]),
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _TopBar extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        MediaQuery.of(context).padding.top + 12,
        16,
        14,
      ),
      decoration: const BoxDecoration(gradient: AppColors.navyGradient),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => context.pop(),
            child: Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(30),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.arrow_back_ios_new_rounded,
                  color: Colors.white, size: 15),
            ),
          ),
          const SizedBox(width: 12),
          Text(
            'Sondages',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: Colors.white.withAlpha(25),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              'Gagnez des FCFA',
              style: GoogleFonts.nunito(
                fontSize: 11,
                color: Colors.white.withAlpha(220),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Survey card
// ---------------------------------------------------------------------------

class _SurveyCard extends StatelessWidget {
  const _SurveyCard({required this.survey});

  final SurveyModel survey;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => context.push('/surveys/${survey.id}'),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.white,
          border: Border.all(color: AppColors.border),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: AppColors.sky.withAlpha(10),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            // Icon
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                gradient: AppColors.skyGradientDiagonal,
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(Icons.assignment_rounded,
                  color: Colors.white, size: 26),
            ),
            const SizedBox(width: 14),

            // Content
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    survey.title,
                    style: GoogleFonts.nunito(
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                      color: AppColors.navy,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (survey.description != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      survey.description!,
                      style: GoogleFonts.nunito(
                        fontSize: 11,
                        color: AppColors.muted,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      _Badge(
                        label: Formatters.currency(survey.rewardAmount),
                        color: AppColors.warn,
                        bgColor: AppColors.warnLight,
                      ),
                      const SizedBox(width: 8),
                      _Badge(
                        label: '+${survey.rewardXp} XP',
                        color: const Color(0xFF7C3AED),
                        bgColor: const Color(0xFFF3E8FF),
                      ),
                      const SizedBox(width: 8),
                      _Badge(
                        label: '${survey.questions.length} questions',
                        color: AppColors.muted,
                        bgColor: AppColors.bg,
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Arrow
            const Icon(Icons.arrow_forward_ios_rounded,
                size: 14, color: AppColors.muted),
          ],
        ),
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({
    required this.label,
    required this.color,
    required this.bgColor,
  });

  final String label;
  final Color color;
  final Color bgColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: GoogleFonts.nunito(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Empty view
// ---------------------------------------------------------------------------

class _EmptyView extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppColors.skyPale,
                borderRadius: BorderRadius.circular(24),
              ),
              child: const Icon(Icons.assignment_outlined,
                  size: 44, color: AppColors.sky),
            ),
            const SizedBox(height: 16),
            Text(
              'Aucun sondage disponible',
              style: GoogleFonts.nunito(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Revenez bientôt, de nouveaux sondages\narrivent régulièrement.',
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 13,
                color: AppColors.muted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Error view
// ---------------------------------------------------------------------------

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline_rounded,
                size: 48, color: AppColors.danger),
            const SizedBox(height: 12),
            Text(
              message.replaceAll('Exception: ', ''),
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(color: AppColors.muted, fontSize: 13),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: onRetry,
              child: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }
}
