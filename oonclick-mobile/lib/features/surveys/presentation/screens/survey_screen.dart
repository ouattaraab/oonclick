import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/survey_model.dart';
import '../../data/repositories/survey_repository.dart';
import '../providers/survey_provider.dart';

/// Écran de sondage — une question par page avec barre de progression.
class SurveyScreen extends ConsumerStatefulWidget {
  const SurveyScreen({super.key, required this.surveyId});

  final int surveyId;

  @override
  ConsumerState<SurveyScreen> createState() => _SurveyScreenState();
}

class _SurveyScreenState extends ConsumerState<SurveyScreen> {
  int _currentPage = 0;
  final List<dynamic> _answers = [];
  bool _isSubmitting = false;

  void _initAnswers(int count) {
    if (_answers.length != count) {
      _answers.clear();
      _answers.addAll(List<dynamic>.filled(count, null));
    }
  }

  Future<void> _submit(SurveyModel survey) async {
    setState(() => _isSubmitting = true);
    try {
      final result = await ref
          .read(surveyRepositoryProvider)
          .submitSurvey(survey.id, List<dynamic>.from(_answers));

      if (mounted) {
        _showSuccessDialog(result.reward, result.xp, result.message);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.toString().replaceAll('Exception: ', ''),
              style: GoogleFonts.nunito(),
            ),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showSuccessDialog(int reward, int xp, String message) {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _SuccessDialog(
        reward: reward,
        xp: xp,
        message: message,
        onDone: () {
          Navigator.of(context).pop();
          ref.read(surveysProvider.notifier).refresh();
          context.pop();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final surveyAsync = ref.watch(surveyDetailProvider(widget.surveyId));

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: surveyAsync.when(
        loading: () => const Center(
          child: CircularProgressIndicator(color: AppColors.sky),
        ),
        error: (err, _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline_rounded,
                  size: 48, color: AppColors.danger),
              const SizedBox(height: 12),
              Text(
                err.toString().replaceAll('Exception: ', ''),
                textAlign: TextAlign.center,
                style: GoogleFonts.nunito(color: AppColors.muted),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => context.pop(),
                child: const Text('Retour'),
              ),
            ],
          ),
        ),
        data: (survey) {
          _initAnswers(survey.questions.length);
          return _SurveyContent(
            survey: survey,
            currentPage: _currentPage,
            answers: _answers,
            isSubmitting: _isSubmitting,
            onNext: () => setState(() => _currentPage++),
            onPrev: () => setState(() => _currentPage--),
            onAnswerChanged: (value) {
              setState(() => _answers[_currentPage] = value);
            },
            onSubmit: () => _submit(survey),
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Survey content with paged questions
// ---------------------------------------------------------------------------

class _SurveyContent extends StatelessWidget {
  const _SurveyContent({
    required this.survey,
    required this.currentPage,
    required this.answers,
    required this.isSubmitting,
    required this.onNext,
    required this.onPrev,
    required this.onAnswerChanged,
    required this.onSubmit,
  });

  final SurveyModel survey;
  final int currentPage;
  final List<dynamic> answers;
  final bool isSubmitting;
  final VoidCallback onNext;
  final VoidCallback onPrev;
  final ValueChanged<dynamic> onAnswerChanged;
  final VoidCallback onSubmit;

  bool get _isLastPage => currentPage == survey.questions.length - 1;
  SurveyQuestion get _currentQuestion => survey.questions[currentPage];

  bool get _canProceed {
    if (!_currentQuestion.required) return true;
    final answer = answers[currentPage];
    if (answer == null) return false;
    if (answer is String) return answer.trim().isNotEmpty;
    if (answer is List) return answer.isNotEmpty;
    return false;
  }

  @override
  Widget build(BuildContext context) {
    final progress = (currentPage + 1) / survey.questions.length;

    return Column(
      children: [
        // Top bar with progress
        _TopBar(
          survey: survey,
          currentPage: currentPage,
          progress: progress,
        ),

        // Question area
        Expanded(
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 250),
            transitionBuilder: (child, animation) => FadeTransition(
              opacity: animation,
              child: SlideTransition(
                position: Tween<Offset>(
                  begin: const Offset(0.05, 0),
                  end: Offset.zero,
                ).animate(animation),
                child: child,
              ),
            ),
            child: KeyedSubtree(
              key: ValueKey(currentPage),
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Question number
                    Text(
                      'Question ${currentPage + 1} / ${survey.questions.length}',
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        color: AppColors.sky,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),

                    // Question text
                    Text(
                      _currentQuestion.text,
                      style: GoogleFonts.nunito(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: AppColors.navy,
                        height: 1.3,
                      ),
                    ),

                    if (_currentQuestion.required)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          '* Réponse obligatoire',
                          style: GoogleFonts.nunito(
                            fontSize: 11,
                            color: AppColors.danger,
                          ),
                        ),
                      ),

                    const SizedBox(height: 24),

                    // Answer input
                    _AnswerInput(
                      question: _currentQuestion,
                      value: answers[currentPage],
                      onChanged: onAnswerChanged,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),

        // Navigation buttons
        _NavigationBar(
          currentPage: currentPage,
          totalPages: survey.questions.length,
          canProceed: _canProceed,
          isSubmitting: isSubmitting,
          isLastPage: _isLastPage,
          onNext: onNext,
          onPrev: onPrev,
          onSubmit: onSubmit,
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar with progress
// ---------------------------------------------------------------------------

class _TopBar extends StatelessWidget {
  const _TopBar({
    required this.survey,
    required this.currentPage,
    required this.progress,
  });

  final SurveyModel survey;
  final int currentPage;
  final double progress;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        MediaQuery.of(context).padding.top + 12,
        16,
        0,
      ),
      decoration: const BoxDecoration(gradient: AppColors.navyGradient),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
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
                  child: const Icon(Icons.close_rounded,
                      color: Colors.white, size: 18),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  survey.title,
                  style: GoogleFonts.nunito(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withAlpha(25),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  Formatters.currency(survey.rewardAmount),
                  style: GoogleFonts.nunito(
                    fontSize: 11,
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Progress bar
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 4,
              backgroundColor: Colors.white.withAlpha(40),
              valueColor:
                  const AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ),
          const SizedBox(height: 12),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Answer input widget
// ---------------------------------------------------------------------------

class _AnswerInput extends StatelessWidget {
  const _AnswerInput({
    required this.question,
    required this.value,
    required this.onChanged,
  });

  final SurveyQuestion question;
  final dynamic value;
  final ValueChanged<dynamic> onChanged;

  @override
  Widget build(BuildContext context) {
    switch (question.type) {
      case 'text':
        return _TextAnswer(
          value: value as String?,
          onChanged: onChanged,
        );
      case 'radio':
        return _RadioAnswer(
          options: question.options ?? [],
          value: value as String?,
          onChanged: onChanged,
        );
      case 'checkbox':
        return _CheckboxAnswer(
          options: question.options ?? [],
          values: value as List<String>?,
          onChanged: onChanged,
        );
      default:
        return const SizedBox.shrink();
    }
  }
}

class _TextAnswer extends StatefulWidget {
  const _TextAnswer({required this.value, required this.onChanged});

  final String? value;
  final ValueChanged<String> onChanged;

  @override
  State<_TextAnswer> createState() => _TextAnswerState();
}

class _TextAnswerState extends State<_TextAnswer> {
  late final TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.value ?? '');
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: _controller,
      onChanged: widget.onChanged,
      maxLines: 4,
      style: GoogleFonts.nunito(fontSize: 14, color: AppColors.navy),
      decoration: InputDecoration(
        hintText: 'Votre réponse…',
        hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
        filled: true,
        fillColor: AppColors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.sky, width: 2),
        ),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }
}

class _RadioAnswer extends StatelessWidget {
  const _RadioAnswer({
    required this.options,
    required this.value,
    required this.onChanged,
  });

  final List<String> options;
  final String? value;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: options.map((option) {
        final isSelected = value == option;
        return GestureDetector(
          onTap: () => onChanged(option),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(
              color: isSelected ? AppColors.skyPale : AppColors.white,
              border: Border.all(
                color: isSelected ? AppColors.sky : AppColors.border,
                width: isSelected ? 2 : 1,
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Container(
                  width: 20,
                  height: 20,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: isSelected ? AppColors.sky : AppColors.border,
                      width: 2,
                    ),
                  ),
                  child: isSelected
                      ? Center(
                          child: Container(
                            width: 10,
                            height: 10,
                            decoration: const BoxDecoration(
                              shape: BoxShape.circle,
                              color: AppColors.sky,
                            ),
                          ),
                        )
                      : null,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    option,
                    style: GoogleFonts.nunito(
                      fontSize: 14,
                      fontWeight: isSelected
                          ? FontWeight.w700
                          : FontWeight.w500,
                      color: isSelected ? AppColors.navy : AppColors.muted,
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _CheckboxAnswer extends StatelessWidget {
  const _CheckboxAnswer({
    required this.options,
    required this.values,
    required this.onChanged,
  });

  final List<String> options;
  final List<String>? values;
  final ValueChanged<List<String>> onChanged;

  @override
  Widget build(BuildContext context) {
    final selected = values ?? <String>[];

    return Column(
      children: options.map((option) {
        final isSelected = selected.contains(option);
        return GestureDetector(
          onTap: () {
            final newValues = List<String>.from(selected);
            if (isSelected) {
              newValues.remove(option);
            } else {
              newValues.add(option);
            }
            onChanged(newValues);
          },
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(
              color: isSelected ? AppColors.skyPale : AppColors.white,
              border: Border.all(
                color: isSelected ? AppColors.sky : AppColors.border,
                width: isSelected ? 2 : 1,
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  width: 20,
                  height: 20,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(5),
                    color: isSelected ? AppColors.sky : Colors.transparent,
                    border: Border.all(
                      color: isSelected ? AppColors.sky : AppColors.border,
                      width: 2,
                    ),
                  ),
                  child: isSelected
                      ? const Icon(Icons.check_rounded,
                          color: Colors.white, size: 14)
                      : null,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    option,
                    style: GoogleFonts.nunito(
                      fontSize: 14,
                      fontWeight: isSelected
                          ? FontWeight.w700
                          : FontWeight.w500,
                      color: isSelected ? AppColors.navy : AppColors.muted,
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }
}

// ---------------------------------------------------------------------------
// Navigation bar
// ---------------------------------------------------------------------------

class _NavigationBar extends StatelessWidget {
  const _NavigationBar({
    required this.currentPage,
    required this.totalPages,
    required this.canProceed,
    required this.isSubmitting,
    required this.isLastPage,
    required this.onNext,
    required this.onPrev,
    required this.onSubmit,
  });

  final int currentPage;
  final int totalPages;
  final bool canProceed;
  final bool isSubmitting;
  final bool isLastPage;
  final VoidCallback onNext;
  final VoidCallback onPrev;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
          16, 12, 16, MediaQuery.of(context).padding.bottom + 16),
      decoration: const BoxDecoration(
        color: AppColors.white,
        border: Border(top: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        children: [
          if (currentPage > 0)
            Expanded(
              child: OutlinedButton(
                onPressed: onPrev,
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  side: const BorderSide(color: AppColors.border),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  'Précédent',
                  style: GoogleFonts.nunito(
                    fontWeight: FontWeight.w700,
                    color: AppColors.muted,
                  ),
                ),
              ),
            ),
          if (currentPage > 0) const SizedBox(width: 12),
          Expanded(
            flex: 2,
            child: isLastPage
                ? SkyGradientButton(
                    label: isSubmitting ? 'Envoi…' : 'Terminer et recevoir ma récompense',
                    onPressed: (canProceed && !isSubmitting) ? onSubmit : null,
                    isLoading: isSubmitting,
                    height: 50,
                    borderRadius: 12,
                  )
                : SkyGradientButton(
                    label: 'Suivant',
                    onPressed: canProceed ? onNext : null,
                    height: 50,
                    borderRadius: 12,
                  ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Success dialog
// ---------------------------------------------------------------------------

class _SuccessDialog extends StatelessWidget {
  const _SuccessDialog({
    required this.reward,
    required this.xp,
    required this.message,
    required this.onDone,
  });

  final int reward;
  final int xp;
  final String message;
  final VoidCallback onDone;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: AppColors.skyGradientDiagonal,
              ),
              child: const Icon(Icons.task_alt_rounded,
                  color: Colors.white, size: 40),
            ),
            const SizedBox(height: 16),
            Text(
              'Merci !',
              style: GoogleFonts.nunito(
                fontSize: 22,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 13,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 20),

            // Rewards
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _RewardChip(
                  label: '+${Formatters.currency(reward)}',
                  icon: Icons.account_balance_wallet_rounded,
                  color: AppColors.warn,
                  bgColor: AppColors.warnLight,
                ),
                const SizedBox(width: 12),
                _RewardChip(
                  label: '+$xp XP',
                  icon: Icons.star_rounded,
                  color: const Color(0xFF7C3AED),
                  bgColor: const Color(0xFFF3E8FF),
                ),
              ],
            ),

            const SizedBox(height: 24),
            SkyGradientButton(
              label: 'Continuer',
              onPressed: onDone,
              height: 46,
            ),
          ],
        ),
      ),
    );
  }
}

class _RewardChip extends StatelessWidget {
  const _RewardChip({
    required this.label,
    required this.icon,
    required this.color,
    required this.bgColor,
  });

  final String label;
  final IconData icon;
  final Color color;
  final Color bgColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 6),
          Text(
            label,
            style: GoogleFonts.nunito(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}
