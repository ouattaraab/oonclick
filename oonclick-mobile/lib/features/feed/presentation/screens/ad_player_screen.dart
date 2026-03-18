import 'dart:async';

import 'package:chewie/chewie.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:video_player/video_player.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/campaign_model.dart';
import '../../data/repositories/feed_repository.dart';
import '../providers/feed_provider.dart';

/// Full-screen ad player screen.
///
/// Lifecycle:
/// 1. Loads the campaign from the feed provider by [campaignId].
/// 2. Calls `startView()` to register the session.
/// 3. Tracks video progress — auto-completes at 80% watch threshold.
/// 4. Calls `completeView()` and shows a result dialog.
///
/// Supports 4 formats:
/// - `video`  — standard full-screen playback
/// - `scratch` — grattage overlay revealed at 50% progress
/// - `quiz`   — MCQ shown after video ends (any answer credits)
/// - `flash`  — short-form with prominent countdown
class AdPlayerScreen extends ConsumerStatefulWidget {
  const AdPlayerScreen({super.key, required this.campaignId});

  final int campaignId;

  @override
  ConsumerState<AdPlayerScreen> createState() => _AdPlayerScreenState();
}

class _AdPlayerScreenState extends ConsumerState<AdPlayerScreen> {
  VideoPlayerController? _videoCtrl;
  ChewieController? _chewieCtrl;

  CampaignModel? _campaign;
  bool _loadError = false;
  bool _isCompleting = false;
  bool _hasCompleted = false;

  // Scratch / quiz state
  bool _scratchUnlocked = false;
  bool _scratchDone = false;
  bool _quizAnswered = false;

  // Progress tracking
  Timer? _progressTimer;
  int _watchedSeconds = 0;

  @override
  void initState() {
    super.initState();
    SystemChrome.setPreferredOrientations([
      DeviceOrientation.portraitUp,
      DeviceOrientation.landscapeLeft,
      DeviceOrientation.landscapeRight,
    ]);
    _initPlayer();
  }

  @override
  void dispose() {
    _progressTimer?.cancel();
    _chewieCtrl?.dispose();
    _videoCtrl?.dispose();
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    super.dispose();
  }

  // ---------------------------------------------------------------------------
  // Initialisation
  // ---------------------------------------------------------------------------

  Future<void> _initPlayer() async {
    // Resolve campaign from local feed cache.
    final campaigns =
        ref.read(feedProvider).valueOrNull ?? [];
    final campaign = campaigns.cast<CampaignModel?>().firstWhere(
          (c) => c?.id == widget.campaignId,
          orElse: () => null,
        );

    if (campaign == null) {
      setState(() => _loadError = true);
      return;
    }

    setState(() => _campaign = campaign);

    // Start the server-side view registration (best-effort).
    await ref
        .read(adViewProvider.notifier)
        .startView(widget.campaignId);

    // Initialise video controller.
    try {
      _videoCtrl =
          VideoPlayerController.networkUrl(Uri.parse(campaign.mediaUrl));
      await _videoCtrl!.initialize();

      _chewieCtrl = ChewieController(
        videoPlayerController: _videoCtrl!,
        autoPlay: true,
        looping: false,
        allowFullScreen: true,
        allowMuting: true,
        showControls: false, // Custom controls only.
        aspectRatio: _videoCtrl!.value.aspectRatio,
      );

      _videoCtrl!.addListener(_onVideoProgress);
      if (mounted) setState(() {});
    } catch (e) {
      setState(() => _loadError = true);
    }
  }

  // ---------------------------------------------------------------------------
  // Video progress listener
  // ---------------------------------------------------------------------------

  void _onVideoProgress() {
    if (_videoCtrl == null || !_videoCtrl!.value.isInitialized) return;

    final position = _videoCtrl!.value.position.inSeconds;
    final duration = _videoCtrl!.value.duration.inSeconds;
    if (duration == 0) return;

    setState(() => _watchedSeconds = position);

    final progressRatio = position / duration;

    // Unlock scratch overlay at 50%.
    if (_campaign!.isScratch && progressRatio >= 0.5 && !_scratchUnlocked) {
      setState(() => _scratchUnlocked = true);
    }

    // Auto-complete at 80% (if not already done).
    if (!_hasCompleted && progressRatio >= 0.8) {
      if (_campaign!.isQuiz) {
        // For quiz, wait until video ends then show question.
        if (_videoCtrl!.value.position >= _videoCtrl!.value.duration) {
          _showQuiz();
        }
      } else if (!_campaign!.isScratch || _scratchDone) {
        _triggerComplete();
      }
    }
  }

  // ---------------------------------------------------------------------------
  // Complete view
  // ---------------------------------------------------------------------------

  Future<void> _triggerComplete() async {
    if (_hasCompleted || _isCompleting) return;
    setState(() {
      _hasCompleted = true;
      _isCompleting = true;
    });

    _videoCtrl?.pause();

    final result = await ref
        .read(adViewProvider.notifier)
        .completeView(widget.campaignId, _watchedSeconds);

    ref.read(feedProvider.notifier).markViewed(widget.campaignId);

    if (!mounted) return;
    setState(() => _isCompleting = false);

    _showResultDialog(result);
  }

  void _showResultDialog(ViewResult? result) {
    final credited = result?.credited ?? false;
    final amount = result?.amount ?? 0;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: credited
                    ? AppTheme.successLight
                    : AppTheme.errorLight,
                shape: BoxShape.circle,
              ),
              child: Icon(
                credited
                    ? Icons.check_circle_rounded
                    : Icons.info_outline_rounded,
                color: credited ? AppTheme.success : AppTheme.error,
                size: 36,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              credited ? 'Bravo !' : 'Vue enregistrée',
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              credited
                  ? 'Vous avez gagné ${Formatters.currency(amount)}'
                  : (result?.reason ?? 'Pub déjà visionnée'),
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppTheme.textSecondary,
                fontSize: 14,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              context.pop();
            },
            child: const Text('Continuer'),
          ),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Quiz logic
  // ---------------------------------------------------------------------------

  void _showQuiz() {
    if (_quizAnswered) return;
    _videoCtrl?.pause();
    setState(() {}); // triggers quiz overlay render
  }

  void _onQuizAnswer(int index) {
    setState(() {
      _quizAnswered = true;
    });
    Future.delayed(const Duration(milliseconds: 800), _triggerComplete);
  }

  // ---------------------------------------------------------------------------
  // Scratch gesture
  // ---------------------------------------------------------------------------

  void _onScratchComplete() {
    if (_scratchDone) return;
    setState(() => _scratchDone = true);
    _triggerComplete();
  }

  // ---------------------------------------------------------------------------
  // Build
  // ---------------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    if (_loadError) {
      return Scaffold(
        backgroundColor: Colors.black,
        appBar: AppBar(
          backgroundColor: Colors.black,
          foregroundColor: Colors.white,
          leading: BackButton(onPressed: () => context.pop()),
        ),
        body: const Center(
          child: Text(
            'Impossible de charger la publicité.',
            style: TextStyle(color: Colors.white),
          ),
        ),
      );
    }

    if (_campaign == null || _chewieCtrl == null) {
      return const Scaffold(
        backgroundColor: Colors.black,
        body: Center(
          child: CircularProgressIndicator(color: AppTheme.primary),
        ),
      );
    }

    final campaign = _campaign!;
    final duration = _videoCtrl!.value.duration.inSeconds;
    final progress =
        duration > 0 ? (_watchedSeconds / duration).clamp(0.0, 1.0) : 0.0;

    return Scaffold(
      backgroundColor: Colors.black,
      body: SafeArea(
        child: Stack(
          children: [
            // Video
            Center(child: Chewie(controller: _chewieCtrl!)),

            // Non-seekable progress bar
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: LinearProgressIndicator(
                value: progress,
                backgroundColor:
                    Colors.white.withAlpha(40),
                valueColor: const AlwaysStoppedAnimation<Color>(
                    AppTheme.primary),
                minHeight: 4,
              ),
            ),

            // Top overlay — title, format, amount
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: Container(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withAlpha(160),
                      Colors.transparent,
                    ],
                  ),
                ),
                child: Row(
                  children: [
                    // Back button
                    GestureDetector(
                      onTap: () => context.pop(),
                      child: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: Colors.white.withAlpha(30),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.arrow_back_ios_new_rounded,
                          color: Colors.white,
                          size: 16,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        campaign.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: 15,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    // Earn badge
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppTheme.success,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        '+ ${Formatters.currency(campaign.amount)}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Flash countdown overlay
            if (campaign.isFlash)
              Positioned(
                top: 70,
                right: 16,
                child: _FlashCountdown(
                  remaining: duration - _watchedSeconds,
                ),
              ),

            // Scratch overlay
            if (campaign.isScratch && !_scratchDone)
              _ScratchOverlay(
                unlocked: _scratchUnlocked,
                onScratchComplete: _onScratchComplete,
              ),

            // Quiz overlay (shown after video ends)
            if (campaign.isQuiz && !_quizAnswered && _watchedSeconds >= duration)
              _QuizOverlay(onAnswer: _onQuizAnswer),

            // Completing spinner
            if (_isCompleting)
              Container(
                color: Colors.black.withAlpha(120),
                child: const Center(
                  child: CircularProgressIndicator(
                    color: AppTheme.primary,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Flash countdown badge
// ---------------------------------------------------------------------------

class _FlashCountdown extends StatelessWidget {
  const _FlashCountdown({required this.remaining});

  final int remaining;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: AppTheme.warning,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(40),
            blurRadius: 8,
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.bolt_rounded, color: Colors.white, size: 16),
          const SizedBox(width: 4),
          Text(
            '${remaining}s',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              fontSize: 16,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Scratch overlay
// ---------------------------------------------------------------------------

class _ScratchOverlay extends StatefulWidget {
  const _ScratchOverlay({
    required this.unlocked,
    required this.onScratchComplete,
  });

  final bool unlocked;
  final VoidCallback onScratchComplete;

  @override
  State<_ScratchOverlay> createState() => _ScratchOverlayState();
}

class _ScratchOverlayState extends State<_ScratchOverlay> {
  final Set<Offset> _scratchedPoints = {};
  static const double _brushRadius = 24;
  static const double _canvasSize = 220;

  double get _scratchRatio =>
      _scratchedPoints.length /
      ((_canvasSize / _brushRadius) * (_canvasSize / _brushRadius));

  @override
  Widget build(BuildContext context) {
    if (!widget.unlocked) {
      return Positioned.fill(
        child: Container(
          color: Colors.black.withAlpha(180),
          child: const Center(
            child: Text(
              'Continuez à regarder pour gratter…',
              style: TextStyle(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
      );
    }

    return Positioned.fill(
      child: Container(
        color: Colors.black.withAlpha(160),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Grattez pour révéler votre gain !',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 20),
              GestureDetector(
                onPanUpdate: (details) {
                  final localPos = details.localPosition;
                  setState(() {
                    _scratchedPoints.add(localPos);
                  });
                  if (_scratchRatio >= 0.4) {
                    widget.onScratchComplete();
                  }
                },
                child: SizedBox(
                  width: _canvasSize,
                  height: _canvasSize,
                  child: CustomPaint(
                    painter: _ScratchPainter(
                        points: _scratchedPoints,
                        brushRadius: _brushRadius),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ScratchPainter extends CustomPainter {
  _ScratchPainter({required this.points, required this.brushRadius});

  final Set<Offset> points;
  final double brushRadius;

  @override
  void paint(Canvas canvas, Size size) {
    // Silver scratch card background
    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width, size.height),
      Paint()..color = const Color(0xFFB0B0B0),
    );

    // Prize revealed below
    final textPainter = TextPainter(
      text: const TextSpan(
        text: '500\nFCFA',
        style: TextStyle(
          color: AppTheme.success,
          fontSize: 36,
          fontWeight: FontWeight.w900,
          height: 1.2,
        ),
      ),
      textDirection: TextDirection.ltr,
      textAlign: TextAlign.center,
    )..layout(maxWidth: size.width);
    textPainter.paint(
      canvas,
      Offset(
          (size.width - textPainter.width) / 2,
          (size.height - textPainter.height) / 2),
    );

    // Scratch off layer
    final scratchPaint = Paint()
      ..blendMode = BlendMode.clear
      ..strokeCap = StrokeCap.round
      ..strokeWidth = brushRadius * 2;

    for (final p in points) {
      canvas.drawCircle(p, brushRadius, scratchPaint);
    }
  }

  @override
  bool shouldRepaint(_ScratchPainter old) => old.points != points;
}

// ---------------------------------------------------------------------------
// Quiz overlay
// ---------------------------------------------------------------------------

const _quizQuestions = [
  {
    'question': 'Que pensez-vous de cette publicité ?',
    'answers': ['Très intéressante', 'Intéressante', 'Sans avis'],
  },
];

class _QuizOverlay extends StatelessWidget {
  const _QuizOverlay({required this.onAnswer});

  final ValueChanged<int> onAnswer;

  @override
  Widget build(BuildContext context) {
    final q = _quizQuestions.first;
    final answers = q['answers'] as List<String>;

    return Positioned.fill(
      child: Container(
        color: Colors.black.withAlpha(200),
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(
                  horizontal: 12, vertical: 5),
              decoration: BoxDecoration(
                color: const Color(0xFF3B82F6),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Text(
                'QUIZ',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 12,
                ),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              q['question'] as String,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 28),
            ...answers.asMap().entries.map((entry) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () => onAnswer(entry.key),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: AppTheme.textPrimary,
                      padding:
                          const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      entry.value,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                      ),
                    ),
                  ),
                ),
              );
            }),
          ],
        ),
      ),
    );
  }
}
