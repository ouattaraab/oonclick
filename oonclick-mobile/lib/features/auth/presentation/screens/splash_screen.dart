import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/services/app_install_service.dart';
import '../../../../core/services/app_version_service.dart';
import '../../../../core/services/hive_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';

// ---------------------------------------------------------------------------
// Onboarding data
// ---------------------------------------------------------------------------

class _OnboardingSlide {
  const _OnboardingSlide({
    required this.emoji,
    required this.title,
    required this.subtitle,
    required this.bgColor,
  });

  final String emoji;
  final String title;
  final String subtitle;
  final Color bgColor;
}

const _slides = [
  _OnboardingSlide(
    emoji: '📺',
    title: 'Regardez des publicités',
    subtitle:
        'Découvrez des pubs de marques locales et internationales adaptées à vos centres d\'intérêt.',
    bgColor: AppColors.skyPale,
  ),
  _OnboardingSlide(
    emoji: '💰',
    title: 'Gagnez en FCFA',
    subtitle:
        'Chaque pub regardée vous crédite instantanément en FCFA directement dans votre portefeuille.',
    bgColor: Color(0xFFE8F8EE),
  ),
  _OnboardingSlide(
    emoji: '📱',
    title: 'Retirez via Mobile Money',
    subtitle:
        'Transférez vos gains sur Orange Money, MTN Money, Moov Money ou Wave en quelques secondes.',
    bgColor: Color(0xFFFEF3C7),
  ),
];

// ---------------------------------------------------------------------------
// Splash Screen
// ---------------------------------------------------------------------------

/// Entry-point screen.
///
/// Boot sequence:
/// 1. Show brand splash for 2 s with fade animation.
/// 2. Register / update app install tracking (fire-and-forget).
/// 3. Check for a forced update → show blocking dialog if required.
/// 4. If first-time user → show onboarding slides.
/// 5. Resolve auth state and redirect.
class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _fadeCtrl;
  late final Animation<double> _fade;

  bool _showOnboarding = false;

  @override
  void initState() {
    super.initState();

    _fadeCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
    _fade = CurvedAnimation(parent: _fadeCtrl, curve: Curves.easeIn);
    _fadeCtrl.forward();

    _boot();
  }

  @override
  void dispose() {
    _fadeCtrl.dispose();
    super.dispose();
  }

  Future<void> _boot() async {
    await Future.delayed(const Duration(seconds: 2));
    if (!mounted) return;

    // 1. Register install — fire-and-forget, never blocks the boot sequence.
    unawaited(AppInstallService.registerOrUpdate());

    // 2. Check for a forced app update.
    final updateCheck = await AppVersionService.checkForUpdate();
    if (!mounted) return;

    if (updateCheck != null && updateCheck.requiresUpdate) {
      _showForceUpdateDialog(
        storeUrl: updateCheck.storeUrl,
        releaseNotes: updateCheck.releaseNotes,
      );
      return; // Halt the boot sequence — user must update.
    }

    // 3. First-time onboarding check.
    final isFirstTime = HiveService.getBool('onboarding_done') != true;
    if (isFirstTime) {
      setState(() => _showOnboarding = true);
      return;
    }

    await _resolveAuth();
  }

  Future<void> _resolveAuth() async {
    await ref.read(authProvider.future);
    if (!mounted) return;

    final authState = ref.read(authStateProvider);

    if (authState.isAuthenticated) {
      if (authState.user!.isSubscriber && authState.user!.name == null) {
        context.go('/auth/complete-profile');
      } else {
        context.go('/feed');
      }
    } else {
      context.go('/auth/register');
    }
  }

  void _onOnboardingDone() async {
    await HiveService.setBool('onboarding_done', true);
    if (!mounted) return;
    await _resolveAuth();
  }

  // ---------------------------------------------------------------------------
  // Force update dialog — non-dismissible
  // ---------------------------------------------------------------------------

  void _showForceUpdateDialog({
    String? storeUrl,
    String? releaseNotes,
  }) {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => PopScope(
        canPop: false,
        child: Dialog(
          backgroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icon
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    gradient: AppColors.skyGradientDiagonal,
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.sky.withAlpha(60),
                        blurRadius: 16,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.system_update_rounded,
                    color: Colors.white,
                    size: 36,
                  ),
                ),
                const SizedBox(height: 20),

                // Title
                Text(
                  'Mise à jour requise',
                  style: GoogleFonts.nunito(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: AppColors.navy,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),

                // Body
                Text(
                  'Une nouvelle version de oon.click est disponible. '
                  'Veuillez mettre à jour l\'application pour continuer.',
                  style: GoogleFonts.nunito(
                    fontSize: 14,
                    color: AppColors.muted,
                    height: 1.55,
                  ),
                  textAlign: TextAlign.center,
                ),

                // Optional release notes
                if (releaseNotes != null && releaseNotes.trim().isNotEmpty) ...[
                  const SizedBox(height: 14),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.skyPale,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Text(
                      releaseNotes.trim(),
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        color: AppColors.navy,
                        height: 1.5,
                      ),
                    ),
                  ),
                ],

                const SizedBox(height: 24),

                // Update button
                SkyGradientButton(
                  label: 'Mettre à jour maintenant',
                  onPressed: storeUrl != null
                      ? () => launchUrl(
                            Uri.parse(storeUrl),
                            mode: LaunchMode.externalApplication,
                          )
                      : null,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Build
  // ---------------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    if (_showOnboarding) {
      return _OnboardingFlow(onDone: _onOnboardingDone);
    }
    return _SplashBrand(fade: _fade);
  }
}

// ---------------------------------------------------------------------------
// Splash brand widget
// ---------------------------------------------------------------------------

class _SplashBrand extends StatelessWidget {
  const _SplashBrand({required this.fade});

  final Animation<double> fade;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          // Radial gradient — subtle sky at the top.
          Positioned(
            top: -80,
            left: -80,
            child: Container(
              width: 320,
              height: 320,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    AppColors.sky.withAlpha(30),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),

          // Center content
          Center(
            child: FadeTransition(
              opacity: fade,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Logo
                  _AppLogo(size: 72),
                  const SizedBox(height: 20),

                  // App name
                  RichText(
                    text: TextSpan(
                      children: [
                        TextSpan(
                          text: 'oon',
                          style: GoogleFonts.nunito(
                            fontSize: 30,
                            fontWeight: FontWeight.w900,
                            color: AppColors.sky,
                          ),
                        ),
                        TextSpan(
                          text: '.click',
                          style: GoogleFonts.nunito(
                            fontSize: 30,
                            fontWeight: FontWeight.w900,
                            color: AppColors.navy,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),

                  // Tagline
                  Text(
                    'Regardez des pubs · Gagnez en FCFA',
                    style: GoogleFonts.nunito(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      color: AppColors.muted,
                    ),
                  ),

                  const SizedBox(height: 52),

                  // Subtle loader
                  SizedBox(
                    width: 24,
                    height: 24,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      valueColor: AlwaysStoppedAnimation<Color>(
                        AppColors.sky.withAlpha(160),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Logo widget (reused throughout the app)
// ---------------------------------------------------------------------------

class _AppLogo extends StatelessWidget {
  const _AppLogo({this.size = 72});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        gradient: AppColors.skyGradientDiagonal,
        borderRadius: BorderRadius.circular(size * 0.25),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(60),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Center(
        child: Icon(
          Icons.play_arrow_rounded,
          color: Colors.white,
          size: size * 0.5,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Onboarding flow
// ---------------------------------------------------------------------------

class _OnboardingFlow extends StatefulWidget {
  const _OnboardingFlow({required this.onDone});

  final VoidCallback onDone;

  @override
  State<_OnboardingFlow> createState() => _OnboardingFlowState();
}

class _OnboardingFlowState extends State<_OnboardingFlow> {
  final _pageCtrl = PageController();
  int _currentPage = 0;

  @override
  void dispose() {
    _pageCtrl.dispose();
    super.dispose();
  }

  void _next() {
    if (_currentPage < _slides.length - 1) {
      _pageCtrl.nextPage(
        duration: const Duration(milliseconds: 350),
        curve: Curves.easeOutCubic,
      );
    } else {
      widget.onDone();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          children: [
            // Skip button
            Align(
              alignment: Alignment.centerRight,
              child: Padding(
                padding: const EdgeInsets.only(top: 12, right: 20),
                child: TextButton(
                  onPressed: widget.onDone,
                  child: Text(
                    'Passer',
                    style: GoogleFonts.nunito(
                      color: AppColors.muted,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                ),
              ),
            ),

            // Pages
            Expanded(
              child: PageView.builder(
                controller: _pageCtrl,
                onPageChanged: (i) => setState(() => _currentPage = i),
                itemCount: _slides.length,
                itemBuilder: (ctx, i) => _OnboardingPage(slide: _slides[i]),
              ),
            ),

            // Dots
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(_slides.length, (i) {
                final isActive = i == _currentPage;
                return AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                  width: isActive ? 24 : 8,
                  height: 8,
                  decoration: BoxDecoration(
                    gradient: isActive ? AppColors.skyGradient : null,
                    color: isActive ? null : AppColors.border,
                    borderRadius: BorderRadius.circular(4),
                  ),
                );
              }),
            ),

            const SizedBox(height: 32),

            // Next / Start button
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: SkyGradientButton(
                label: _currentPage == _slides.length - 1
                    ? 'Commencer'
                    : 'Suivant →',
                onPressed: _next,
              ),
            ),

            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}

class _OnboardingPage extends StatelessWidget {
  const _OnboardingPage({required this.slide});

  final _OnboardingSlide slide;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // Illustration circle
          Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              color: slide.bgColor,
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                slide.emoji,
                style: const TextStyle(fontSize: 42),
              ),
            ),
          ),
          const SizedBox(height: 36),

          Text(
            slide.title,
            textAlign: TextAlign.center,
            style: GoogleFonts.nunito(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: AppColors.navy,
              height: 1.25,
            ),
          ),
          const SizedBox(height: 14),

          Text(
            slide.subtitle,
            textAlign: TextAlign.center,
            style: GoogleFonts.nunito(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: AppColors.muted,
              height: 1.6,
            ),
          ),
        ],
      ),
    );
  }
}
