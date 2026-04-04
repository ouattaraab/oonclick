import 'dart:async';

import 'package:chewie/chewie.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:video_player/video_player.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/campaign_model.dart';
import '../../data/repositories/feed_repository.dart';
import '../providers/feed_provider.dart';

class AdPlayerScreen extends ConsumerStatefulWidget {
  const AdPlayerScreen({super.key, required this.campaignId, this.isReplay = false});
  final int campaignId;
  final bool isReplay;

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
  bool _playing = false;

  bool _scratchUnlocked = false;
  bool _scratchDone = false;
  bool _quizAnswered = false;

  int _watchedSeconds = 0;

  // For image-based campaigns (photo/flash)
  Timer? _imageTimer;
  bool _imageReady = false;

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
    _imageTimer?.cancel();
    _chewieCtrl?.dispose();
    _videoCtrl?.dispose();
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    super.dispose();
  }

  Future<void> _initPlayer() async {
    // Try feed first, then history for replays
    final feedCampaigns = ref.read(feedProvider).valueOrNull ?? [];
    var campaign = feedCampaigns.cast<CampaignModel?>().firstWhere(
          (c) => c?.id == widget.campaignId,
          orElse: () => null,
        );

    // If not in feed (replay), fetch from history
    if (campaign == null && widget.isReplay) {
      try {
        final history = await ref.read(feedRepositoryProvider).getHistory();
        campaign = history.cast<CampaignModel?>().firstWhere(
              (c) => c?.id == widget.campaignId,
              orElse: () => null,
            );
      } catch (_) {}
    }

    if (campaign == null) {
      setState(() => _loadError = true);
      return;
    }

    setState(() => _campaign = campaign);

    // Don't register a view for replays
    if (!widget.isReplay) {
      await ref.read(adViewProvider.notifier).startView(widget.campaignId);
    }

    // Image-based formats (photo, flash) — no video player needed
    if (campaign.isImageBased) {
      setState(() => _imageReady = true);
      return;
    }

    // Video-based formats
    try {
      _videoCtrl =
          VideoPlayerController.networkUrl(Uri.parse(campaign.mediaUrl));
      await _videoCtrl!.initialize();

      _chewieCtrl = ChewieController(
        videoPlayerController: _videoCtrl!,
        autoPlay: false,
        looping: false,
        allowFullScreen: true,
        allowMuting: true,
        showControls: false,
        aspectRatio: _videoCtrl!.value.aspectRatio,
      );

      _videoCtrl!.addListener(_onVideoProgress);
      if (mounted) setState(() {});
    } catch (_) {
      setState(() => _loadError = true);
    }
  }

  void _startImageTimer() {
    _imageTimer?.cancel();
    _imageTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      setState(() => _watchedSeconds++);
      final duration = _campaign!.durationSeconds;
      final ratio = _watchedSeconds / duration;
      if (_campaign!.isScratch && ratio >= 0.5 && !_scratchUnlocked) {
        setState(() => _scratchUnlocked = true);
      }
      if (!_hasCompleted && ratio >= 0.8) {
        timer.cancel();
        _triggerComplete();
      }
    });
  }

  void _onVideoProgress() {
    if (_videoCtrl == null || !_videoCtrl!.value.isInitialized) return;
    final position = _videoCtrl!.value.position.inSeconds;
    final duration = _videoCtrl!.value.duration.inSeconds;
    if (duration == 0) return;
    setState(() => _watchedSeconds = position);
    final ratio = position / duration;
    if (_campaign!.isScratch && ratio >= 0.5 && !_scratchUnlocked) {
      setState(() => _scratchUnlocked = true);
    }
    if (!_hasCompleted && ratio >= 0.8) {
      if (_campaign!.isQuiz) {
        if (_videoCtrl!.value.position >= _videoCtrl!.value.duration) {
          _showQuiz();
        }
      } else if (!_campaign!.isScratch || _scratchDone) {
        _triggerComplete();
      }
    }
  }

  Future<void> _triggerComplete() async {
    if (_hasCompleted || _isCompleting) return;
    setState(() {
      _hasCompleted = true;
      _isCompleting = true;
    });
    _videoCtrl?.pause();
    _imageTimer?.cancel();

    if (widget.isReplay) {
      // Replay mode — no credit, just show "already viewed" dialog
      if (!mounted) return;
      setState(() => _isCompleting = false);
      _showCompletedOverlay(const ViewResult(
        credited: false,
        amount: 0,
        newBalance: 0,
        reason: 'Pub déjà visionnée — aucun crédit supplémentaire.',
      ));
      return;
    }

    final result = await ref
        .read(adViewProvider.notifier)
        .completeView(widget.campaignId, _watchedSeconds);
    ref.read(feedProvider.notifier).markViewed(widget.campaignId);
    if (!mounted) return;
    setState(() => _isCompleting = false);
    _showCompletedOverlay(result);
  }

  void _showCompletedOverlay(ViewResult? result) {
    final credited = result?.credited ?? false;
    final amount = result?.amount ?? 0;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _CompletedDialog(
        credited: credited,
        amount: amount,
        reason: result?.reason,
        onClose: () {
          Navigator.of(context).pop();
          context.pop();
        },
        onAnother: () {
          Navigator.of(context).pop();
          context.pop();
        },
      ),
    );
  }

  void _showQuiz() {
    if (_quizAnswered) return;
    _videoCtrl?.pause();
    setState(() {});
  }

  void _onQuizAnswer(int index) {
    setState(() => _quizAnswered = true);
    Future.delayed(const Duration(milliseconds: 800), _triggerComplete);
  }

  void _onScratchComplete() {
    if (_scratchDone) return;
    setState(() => _scratchDone = true);
    _triggerComplete();
  }

  void _startPlayback() {
    setState(() => _playing = true);
    if (_campaign!.isImageBased) {
      _startImageTimer();
    } else {
      _videoCtrl?.play();
    }
  }

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
          child: Text('Impossible de charger la publicité.',
              style: TextStyle(color: Colors.white)),
        ),
      );
    }

    final isImage = _campaign?.isImageBased ?? false;
    if (_campaign == null || (!isImage && _chewieCtrl == null)) {
      return const Scaffold(
        backgroundColor: Colors.black,
        body: Center(
          child: CircularProgressIndicator(color: AppColors.sky),
        ),
      );
    }

    final campaign = _campaign!;
    final duration = isImage
        ? campaign.durationSeconds
        : _videoCtrl!.value.duration.inSeconds;
    final progress =
        duration > 0 ? (_watchedSeconds / duration).clamp(0.0, 1.0) : 0.0;

    // Pre-play screen
    if (!_playing) {
      return _PrePlayScreen(
        campaign: campaign,
        onPlay: _startPlayback,
        isReplay: widget.isReplay,
      );
    }

    return Scaffold(
      backgroundColor: Colors.black,
      body: SafeArea(
        child: Stack(
          children: [
            // Media content (video or image)
            if (isImage)
              Center(
                child: Image.network(
                  campaign.mediaUrl,
                  fit: BoxFit.contain,
                  width: double.infinity,
                  height: double.infinity,
                  loadingBuilder: (context, child, loadingProgress) {
                    if (loadingProgress == null) return child;
                    return const Center(
                      child: CircularProgressIndicator(color: AppColors.sky),
                    );
                  },
                  errorBuilder: (context, error, stackTrace) => const Center(
                    child: Icon(Icons.broken_image, color: Colors.white54, size: 64),
                  ),
                ),
              )
            else
              Center(child: Chewie(controller: _chewieCtrl!)),

            // Progress bar (top)
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: Column(
                children: [
                  // Top overlay
                  Container(
                    padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
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
                        GestureDetector(
                          onTap: () => context.pop(),
                          child: Container(
                            width: 34,
                            height: 34,
                            decoration: BoxDecoration(
                              color: Colors.white.withAlpha(30),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.arrow_back_ios_new_rounded,
                              color: Colors.white,
                              size: 15,
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        // Brand pill
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.sky.withAlpha(60),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            campaign.format.toUpperCase(),
                            style: GoogleFonts.nunito(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            campaign.title,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: GoogleFonts.nunito(
                              color: Colors.white,
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Earn badge
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            gradient: AppColors.skyGradient,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '+${Formatters.currency(campaign.amount)}',
                            style: GoogleFonts.nunito(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Progress bar 4px
                  LinearProgressIndicator(
                    value: progress,
                    backgroundColor: Colors.white.withAlpha(30),
                    valueColor:
                        const AlwaysStoppedAnimation<Color>(AppColors.sky),
                    minHeight: 4,
                  ),
                ],
              ),
            ),

            // Flash countdown
            if (campaign.isFlash)
              Positioned(
                top: 70,
                right: 16,
                child: _FlashCountdown(remaining: duration - _watchedSeconds),
              ),

            // Scratch overlay
            if (campaign.isScratch && !_scratchDone)
              _ScratchOverlay(
                unlocked: _scratchUnlocked,
                onScratchComplete: _onScratchComplete,
              ),

            // Quiz overlay
            if (campaign.isQuiz &&
                !_quizAnswered &&
                _watchedSeconds >= duration)
              _QuizOverlay(campaign: campaign, onAnswer: _onQuizAnswer),

            // Completing spinner
            if (_isCompleting)
              Container(
                color: Colors.black.withAlpha(120),
                child: const Center(
                  child: CircularProgressIndicator(color: AppColors.sky),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Pre-play screen
// ---------------------------------------------------------------------------

class _PrePlayScreen extends StatelessWidget {
  const _PrePlayScreen({required this.campaign, required this.onPlay, this.isReplay = false});

  final CampaignModel campaign;
  final VoidCallback onPlay;
  final bool isReplay;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
              color: Colors.white,
              child: Row(
                children: [
                  GestureDetector(
                    onTap: () => context.pop(),
                    child: const Icon(Icons.arrow_back_ios_new_rounded,
                        color: AppColors.navy, size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          campaign.title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: GoogleFonts.nunito(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: AppColors.navy,
                          ),
                        ),
                        Text(
                          campaign.format.toUpperCase(),
                          style: GoogleFonts.nunito(
                            fontSize: 11,
                            color: AppColors.muted,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Duration badge
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.skyPale,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      Formatters.duration(campaign.durationSeconds),
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.sky,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    // Thumbnail area
                    Container(
                      height: 180,
                      decoration: BoxDecoration(
                        gradient: AppColors.navyGradientDiagonal,
                        borderRadius: BorderRadius.circular(16),
                        image: campaign.isImageBased
                            ? DecorationImage(
                                image: NetworkImage(campaign.mediaUrl),
                                fit: BoxFit.cover,
                              )
                            : null,
                      ),
                      child: Stack(
                        children: [
                          Center(
                            child: Container(
                              width: 64,
                              height: 64,
                              decoration: BoxDecoration(
                                color: Colors.white.withAlpha(40),
                                shape: BoxShape.circle,
                              ),
                              child: Icon(
                                campaign.isImageBased
                                    ? Icons.visibility_rounded
                                    : Icons.play_arrow_rounded,
                                color: Colors.white,
                                size: 38,
                              ),
                            ),
                          ),
                          // Format badge
                          Positioned(
                            top: 12,
                            left: 12,
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: AppColors.sky,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                campaign.format.toUpperCase(),
                                style: GoogleFonts.nunito(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Reward preview
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: isReplay
                            ? const Color(0xFFFEF3C7)
                            : AppColors.skyPale,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isReplay
                              ? const Color(0xFFFCD34D)
                              : AppColors.border,
                        ),
                      ),
                      child: Row(
                        children: [
                          Text(isReplay ? '🔄' : '💰',
                              style: const TextStyle(fontSize: 24)),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  isReplay
                                      ? 'Déjà visionnée'
                                      : 'Votre récompense',
                                  style: GoogleFonts.nunito(
                                    fontSize: 12,
                                    color: AppColors.muted,
                                  ),
                                ),
                                Text(
                                  isReplay
                                      ? 'Pas de crédit'
                                      : '+${Formatters.currency(campaign.amount)}',
                                  style: GoogleFonts.nunito(
                                    fontSize: 20,
                                    fontWeight: FontWeight.w900,
                                    color: isReplay
                                        ? AppColors.warn
                                        : AppColors.sky,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          if (!isReplay)
                            Text(
                              'Regardez ${(campaign.durationSeconds * 0.8).ceil()}s',
                              style: GoogleFonts.nunito(
                                fontSize: 12,
                                color: AppColors.sky2,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    Text(
                      isReplay
                          ? 'Vous avez déjà regardé cette publicité. Vous pouvez la revoir mais aucun crédit ne sera ajouté.'
                          : 'Regardez cette publicité jusqu\'à la fin pour recevoir votre crédit FCFA instantanément sur votre portefeuille.',
                      style: GoogleFonts.nunito(
                        fontSize: 13,
                        color: AppColors.muted,
                        height: 1.6,
                      ),
                      textAlign: TextAlign.center,
                    ),

                    const SizedBox(height: 24),

                    SkyGradientButton(
                      label: isReplay
                          ? 'Revoir la pub'
                          : 'Regarder maintenant',
                      onPressed: onPlay,
                    ),
                  ],
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
// Completed dialog
// ---------------------------------------------------------------------------

class _CompletedDialog extends StatelessWidget {
  const _CompletedDialog({
    required this.credited,
    required this.amount,
    required this.onClose,
    required this.onAnother,
    this.reason,
  });

  final bool credited;
  final int amount;
  final String? reason;
  final VoidCallback onClose;
  final VoidCallback onAnother;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Success circle
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                gradient: credited
                    ? const LinearGradient(
                        colors: [Color(0xFF16A34A), Color(0xFF22C55E)],
                      )
                    : const LinearGradient(
                        colors: [AppColors.muted, AppColors.navy],
                      ),
                shape: BoxShape.circle,
              ),
              child: Icon(
                credited ? Icons.check_rounded : Icons.info_outline_rounded,
                color: Colors.white,
                size: 36,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              credited ? '+${Formatters.currency(amount)} crédité !' : 'Vue enregistrée',
              style: GoogleFonts.nunito(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            if (credited)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.skyPale,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  'Solde mis à jour instantanément',
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    color: AppColors.sky2,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              )
            else
              Text(
                reason ?? 'Pub déjà visionnée',
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  color: AppColors.muted,
                ),
                textAlign: TextAlign.center,
              ),
            const SizedBox(height: 20),
            SkyGradientButton(label: 'Voir une autre pub', onPressed: onAnother),
            const SizedBox(height: 10),
            TextButton(
              onPressed: onClose,
              child: Text(
                'Retour au fil',
                style: GoogleFonts.nunito(
                  color: AppColors.muted,
                  fontWeight: FontWeight.w600,
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
// Flash countdown
// ---------------------------------------------------------------------------

class _FlashCountdown extends StatelessWidget {
  const _FlashCountdown({required this.remaining});
  final int remaining;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.warn,
        borderRadius: BorderRadius.circular(24),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.bolt_rounded, color: Colors.white, size: 16),
          const SizedBox(width: 4),
          Text(
            '${remaining}s',
            style: GoogleFonts.nunito(
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
  const _ScratchOverlay({required this.unlocked, required this.onScratchComplete});
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
          child: Center(
            child: Text(
              'Continuez à regarder pour gratter…',
              style: GoogleFonts.nunito(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.w700,
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
              Text(
                'Grattez pour révéler votre gain !',
                style: GoogleFonts.nunito(
                  color: Colors.white,
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 20),
              GestureDetector(
                onPanUpdate: (details) {
                  setState(() => _scratchedPoints.add(details.localPosition));
                  if (_scratchRatio >= 0.4) widget.onScratchComplete();
                },
                child: SizedBox(
                  width: _canvasSize,
                  height: _canvasSize,
                  child: CustomPaint(
                    painter: _ScratchPainter(
                        points: _scratchedPoints, brushRadius: _brushRadius),
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
    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width, size.height),
      Paint()..color = const Color(0xFFB0B0B0),
    );
    final tp = TextPainter(
      text: TextSpan(
        text: '60\nFCFA',
        style: GoogleFonts.nunito(
          color: AppColors.success,
          fontSize: 36,
          fontWeight: FontWeight.w900,
          height: 1.2,
        ),
      ),
      textDirection: TextDirection.ltr,
      textAlign: TextAlign.center,
    )..layout(maxWidth: size.width);
    tp.paint(canvas,
        Offset((size.width - tp.width) / 2, (size.height - tp.height) / 2));
    final p = Paint()
      ..blendMode = BlendMode.clear
      ..strokeCap = StrokeCap.round
      ..strokeWidth = brushRadius * 2;
    for (final pt in points) canvas.drawCircle(pt, brushRadius, p);
  }

  @override
  bool shouldRepaint(_ScratchPainter old) => old.points != points;
}

// ---------------------------------------------------------------------------
// Quiz overlay
// ---------------------------------------------------------------------------

/// Built-in engagement questions shown when the campaign provides no quiz_data.
const _kFallbackQuizQuestions = <Map<String, dynamic>>[
  {
    'question': 'Que pensez-vous de cette publicité ?',
    'answers': ['Très intéressante', 'Intéressante', 'Sans avis'],
  },
  {
    'question': 'Recommanderiez-vous ce produit ?',
    'answers': ['Oui, certainement', 'Peut-être', 'Non'],
  },
  {
    'question': 'Ce produit vous intéresse-t-il ?',
    'answers': ['Beaucoup', 'Un peu', 'Pas du tout'],
  },
];

class _QuizOverlay extends StatefulWidget {
  const _QuizOverlay({required this.campaign, required this.onAnswer});

  final CampaignModel campaign;
  final ValueChanged<int> onAnswer;

  @override
  State<_QuizOverlay> createState() => _QuizOverlayState();
}

class _QuizOverlayState extends State<_QuizOverlay>
    with TickerProviderStateMixin {
  late final List<Map<String, dynamic>> _questions;

  int _questionIndex = 0;
  int? _selectedAnswer;
  bool _showFeedback = false;

  late AnimationController _fadeCtrl;
  late AnimationController _scaleCtrl;
  late Animation<double> _fadeAnim;
  late Animation<double> _scaleAnim;

  @override
  void initState() {
    super.initState();

    // Prefer campaign-supplied questions; fall back to built-ins.
    final campaignQuiz = widget.campaign.quizData;
    if (campaignQuiz != null && campaignQuiz.isNotEmpty) {
      _questions = campaignQuiz;
    } else {
      // Pick one random engagement question from the fallback set so the
      // experience varies across sessions without exhausting the viewer.
      final idx = widget.campaign.id % _kFallbackQuizQuestions.length;
      _questions = List.from(_kFallbackQuizQuestions);
      // Rotate so each campaign consistently starts on a different question.
      _questions.insert(0, _questions.removeAt(idx));
    }

    _fadeCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );
    _scaleCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );
    _fadeAnim = CurvedAnimation(parent: _fadeCtrl, curve: Curves.easeOut);
    _scaleAnim = Tween<double>(begin: 0.92, end: 1.0)
        .animate(CurvedAnimation(parent: _scaleCtrl, curve: Curves.easeOut));

    _fadeCtrl.forward();
    _scaleCtrl.forward();
  }

  @override
  void dispose() {
    _fadeCtrl.dispose();
    _scaleCtrl.dispose();
    super.dispose();
  }

  Future<void> _handleAnswer(int index) async {
    if (_selectedAnswer != null) return; // prevent double-tap
    setState(() {
      _selectedAnswer = index;
      _showFeedback = true;
    });

    // Wait for the green highlight + feedback message to be visible.
    await Future<void>.delayed(const Duration(milliseconds: 900));

    if (!mounted) return;

    if (_questionIndex < _questions.length - 1) {
      // Animate out, advance question, animate in.
      await _fadeCtrl.reverse();
      await _scaleCtrl.reverse();
      setState(() {
        _questionIndex++;
        _selectedAnswer = null;
        _showFeedback = false;
      });
      _fadeCtrl.forward();
      _scaleCtrl.forward();
    } else {
      // All questions answered — notify parent.
      widget.onAnswer(index);
    }
  }

  @override
  Widget build(BuildContext context) {
    final q = _questions[_questionIndex];
    final question = q['question'] as String? ?? '';
    final rawAnswers = q['answers'];
    final answers = (rawAnswers is List)
        ? rawAnswers.map((e) => e.toString()).toList()
        : <String>[];

    final progress = (_questionIndex + 1) / _questions.length;

    return Positioned.fill(
      child: FadeTransition(
        opacity: _fadeAnim,
        child: Container(
          color: Colors.black.withAlpha(210),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: ScaleTransition(
            scale: _scaleAnim,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Badge row
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 5),
                      decoration: BoxDecoration(
                        gradient: AppColors.skyGradient,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        'QUIZ',
                        style: GoogleFonts.nunito(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 12,
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Text(
                      '${_questionIndex + 1} / ${_questions.length}',
                      style: GoogleFonts.nunito(
                        color: Colors.white.withAlpha(180),
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
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
                        const AlwaysStoppedAnimation<Color>(AppColors.sky),
                  ),
                ),

                const SizedBox(height: 24),

                // Question text
                Text(
                  question,
                  textAlign: TextAlign.center,
                  style: GoogleFonts.nunito(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    height: 1.35,
                  ),
                ),

                const SizedBox(height: 28),

                // Answer buttons
                ...answers.asMap().entries.map((entry) {
                  final isSelected = _selectedAnswer == entry.key;
                  final isDisabled =
                      _selectedAnswer != null && !isSelected;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: isSelected
                            ? AppColors.success
                            : Colors.white.withAlpha(isDisabled ? 80 : 255),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isSelected
                              ? AppColors.success
                              : Colors.transparent,
                          width: 2,
                        ),
                      ),
                      child: Material(
                        color: Colors.transparent,
                        child: InkWell(
                          borderRadius: BorderRadius.circular(12),
                          onTap: _selectedAnswer == null
                              ? () => _handleAnswer(entry.key)
                              : null,
                          child: Padding(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16, vertical: 14),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    entry.value,
                                    style: GoogleFonts.nunito(
                                      fontWeight: FontWeight.w600,
                                      fontSize: 15,
                                      color: isSelected
                                          ? Colors.white
                                          : AppColors.navy,
                                    ),
                                  ),
                                ),
                                if (isSelected)
                                  const Icon(
                                    Icons.check_circle_rounded,
                                    color: Colors.white,
                                    size: 20,
                                  ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  );
                }),

                // Feedback message
                AnimatedOpacity(
                  opacity: _showFeedback ? 1.0 : 0.0,
                  duration: const Duration(milliseconds: 300),
                  child: Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 8),
                      decoration: BoxDecoration(
                        color: AppColors.success.withAlpha(30),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                            color: AppColors.success.withAlpha(80)),
                      ),
                      child: Text(
                        _questions.length > 1 &&
                                _questionIndex < _questions.length - 1
                            ? 'Merci ! Question suivante…'
                            : 'Merci pour votre avis !',
                        style: GoogleFonts.nunito(
                          color: Colors.white,
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
