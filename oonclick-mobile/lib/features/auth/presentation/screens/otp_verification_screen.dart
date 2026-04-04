import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/services/device_service.dart';
import '../../../../core/services/firebase_phone_auth_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';

/// OTP verification screen.
///
/// Supports two methods:
/// - `phone` : Firebase Phone Auth (SMS envoyé par Firebase)
/// - `email` : OTP backend (code envoyé par le serveur)
///
/// Receives route extras: phone/email, type, method, verificationId (phone only).
class OtpVerificationScreen extends ConsumerStatefulWidget {
  const OtpVerificationScreen({
    super.key,
    required this.phone,
    required this.type,
    this.method = 'phone',
    this.email,
    this.verificationId,
  });

  final String phone;
  final String type;

  /// `phone` or `email`
  final String method;
  final String? email;
  final String? verificationId;

  @override
  ConsumerState<OtpVerificationScreen> createState() =>
      _OtpVerificationScreenState();
}

class _OtpVerificationScreenState
    extends ConsumerState<OtpVerificationScreen> {
  static const _codeLength = 6;
  static const _resendCooldown = 60;

  final List<TextEditingController> _controllers =
      List.generate(_codeLength, (_) => TextEditingController());
  final List<FocusNode> _focusNodes =
      List.generate(_codeLength, (_) => FocusNode());

  late Timer _timer;
  int _secondsLeft = _resendCooldown;
  bool _canResend = false;
  bool _isLoading = false;
  String? _error;

  /// `phone` = Firebase Phone Auth, `phone_backend` = OTP backend, `email` = OTP email
  bool get _isFirebasePhone => widget.method == 'phone';
  bool get _isPhoneMethod => widget.method == 'phone' || widget.method == 'phone_backend';

  @override
  void initState() {
    super.initState();
    _startTimer();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => _focusNodes.first.requestFocus(),
    );
  }

  @override
  void dispose() {
    _timer.cancel();
    for (final c in _controllers) {
      c.dispose();
    }
    for (final f in _focusNodes) {
      f.dispose();
    }
    super.dispose();
  }

  // ---------------------------------------------------------------------------
  // Timer
  // ---------------------------------------------------------------------------

  void _startTimer() {
    setState(() {
      _secondsLeft = _resendCooldown;
      _canResend = false;
    });
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_secondsLeft <= 1) {
        t.cancel();
        setState(() => _canResend = true);
      } else {
        setState(() => _secondsLeft--);
      }
    });
  }

  String get _timerDisplay {
    final m = (_secondsLeft ~/ 60).toString().padLeft(2, '0');
    final s = (_secondsLeft % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  // ---------------------------------------------------------------------------
  // OTP helpers
  // ---------------------------------------------------------------------------

  String get _currentCode => _controllers.map((c) => c.text).join();

  void _onDigitChanged(int index, String value) {
    if (value.length > 1) {
      final digits = value.replaceAll(RegExp(r'\D'), '');
      for (var i = 0; i < _codeLength && i < digits.length; i++) {
        _controllers[i].text = digits[i];
      }
      final nextIndex =
          (digits.length < _codeLength) ? digits.length : _codeLength - 1;
      _focusNodes[nextIndex].requestFocus();
    } else if (value.isNotEmpty) {
      if (index < _codeLength - 1) {
        _focusNodes[index + 1].requestFocus();
      } else {
        _focusNodes[index].unfocus();
      }
    }

    setState(() => _error = null);

    if (_currentCode.length == _codeLength) {
      _submit();
    }
  }

  void _onKeyEvent(int index, KeyEvent event) {
    if (event is KeyDownEvent &&
        event.logicalKey == LogicalKeyboardKey.backspace &&
        _controllers[index].text.isEmpty &&
        index > 0) {
      _focusNodes[index - 1].requestFocus();
    }
  }

  // ---------------------------------------------------------------------------
  // Submit / Resend
  // ---------------------------------------------------------------------------

  Future<void> _submit() async {
    final code = _currentCode;
    if (code.length != _codeLength) return;

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      if (_isFirebasePhone) {
        await _submitPhoneOtp(code);
      } else {
        // phone_backend et email utilisent l'OTP backend
        await _submitBackendOtp(code);
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
        for (final c in _controllers) {
          c.clear();
        }
        _focusNodes.first.requestFocus();
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  /// Vérification par Firebase Phone Auth.
  Future<void> _submitPhoneOtp(String code) async {
    final firebaseAuth = ref.read(firebasePhoneAuthProvider);

    // Créer le credential Firebase avec le code saisi
    final credential = await firebaseAuth.verifyCode(code);

    // Signer avec Firebase
    final userCred = await firebaseAuth.signInWithCredential(credential);
    final idToken = await userCred.user?.getIdToken();

    if (idToken == null) {
      throw Exception('Impossible de récupérer le token Firebase.');
    }

    // Envoyer le token Firebase au backend pour créer/authentifier
    final fingerprint = await ref.read(deviceFingerprintProvider.future);
    final platform = await ref.read(deviceServiceProvider).getPlatform();

    final success = await ref.read(authProvider.notifier).verifyWithFirebase(
          phone: widget.phone,
          firebaseIdToken: idToken,
          type: widget.type,
          fingerprint: fingerprint,
          platform: platform,
        );

    if (!mounted) return;
    if (success) {
      _navigateAfterSuccess();
    }
  }

  /// Vérification par OTP backend (phone_backend ou email).
  Future<void> _submitBackendOtp(String code) async {
    final fingerprint = await ref.read(deviceFingerprintProvider.future);
    final platform = await ref.read(deviceServiceProvider).getPlatform();

    // Utilise le phone ou l'email selon la méthode
    final identifier = _isPhoneMethod ? widget.phone : (widget.email ?? '');

    final success = await ref.read(authProvider.notifier).verifyOtp(
          phone: identifier,
          code: code,
          type: widget.type,
          fingerprint: fingerprint,
          platform: platform,
        );

    if (!mounted) return;
    if (success) {
      _navigateAfterSuccess();
    }
  }

  void _navigateAfterSuccess() {
    final user = ref.read(currentUserProvider);
    if (user != null && user.isSubscriber && user.name == null) {
      context.go('/auth/complete-profile');
    } else {
      context.go('/feed');
    }
  }

  Future<void> _resend() async {
    if (!_canResend) return;
    try {
      if (_isFirebasePhone) {
        // Renvoyer via Firebase Phone Auth
        final firebaseAuth = ref.read(firebasePhoneAuthProvider);
        final completer = Completer<void>();

        firebaseAuth.resendCode(
          phoneNumber: widget.phone,
          onCodeSent: (verificationId) {
            if (!completer.isCompleted) completer.complete();
          },
          onAutoVerified: (_) {},
          onError: (error) {
            if (!completer.isCompleted) {
              completer.completeError(Exception(error));
            }
          },
        );

        await completer.future;
      } else {
        // Renvoyer via backend (phone_backend ou email)
        final identifier = _isPhoneMethod ? widget.phone : (widget.email ?? '');
        await ref
            .read(authRepositoryProvider)
            .resendOtp(identifier, widget.type);
      }

      if (!mounted) return;
      _startTimer();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Code renvoyé avec succès')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  // ---------------------------------------------------------------------------
  // Build
  // ---------------------------------------------------------------------------

  String get _maskedIdentifier {
    if (_isPhoneMethod) {
      final phone = widget.phone;
      if (phone.length < 4) return phone;
      final prefix = phone.substring(0, 4);
      final rest = phone.substring(4);
      if (rest.length < 8) return phone;
      return '$prefix •• ••• ${rest.substring(rest.length - 4, rest.length - 2)} '
          '${rest.substring(rest.length - 2)}';
    } else {
      // Masquer l'email : a***@gmail.com
      final email = widget.email ?? '';
      final parts = email.split('@');
      if (parts.length != 2) return email;
      final name = parts[0];
      final domain = parts[1];
      if (name.length <= 2) return email;
      return '${name[0]}${'•' * (name.length - 1)}@$domain';
    }
  }

  String get _headerSubtitle {
    if (_isPhoneMethod) {
      return 'Code envoyé par SMS au $_maskedIdentifier';
    } else {
      return 'Code envoyé par e-mail à $_maskedIdentifier';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // Navy header with back button
          _OtpHeader(
            subtitle: _headerSubtitle,
            icon: _isPhoneMethod ? Icons.sms_rounded : Icons.email_rounded,
            onBack: () => context.pop(),
          ),

          // Body
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 32, 24, 32),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Info pill
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: _isPhoneMethod
                          ? AppColors.skyPale
                          : const Color(0xFFFFF7ED),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          _isPhoneMethod
                              ? Icons.phone_android_rounded
                              : Icons.email_rounded,
                          size: 16,
                          color: _isPhoneMethod
                              ? AppColors.sky
                              : const Color(0xFFF59E0B),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          _isPhoneMethod
                              ? 'Vérification par SMS'
                              : 'Vérification par e-mail',
                          style: GoogleFonts.nunito(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: _isPhoneMethod
                                ? AppColors.sky2
                                : const Color(0xFFD97706),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),

                  // OTP boxes
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(_codeLength, (index) {
                      return Padding(
                        padding: EdgeInsets.only(
                          right: index < _codeLength - 1 ? 7 : 0,
                        ),
                        child: _OtpBox(
                          controller: _controllers[index],
                          focusNode: _focusNodes[index],
                          onChanged: (v) => _onDigitChanged(index, v),
                          onKeyEvent: (e) => _onKeyEvent(index, e),
                          hasError: _error != null,
                        ),
                      );
                    }),
                  ),

                  // Error
                  if (_error != null) ...[
                    const SizedBox(height: 14),
                    Text(
                      _error!,
                      style: GoogleFonts.nunito(
                        color: AppColors.danger,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ],

                  const SizedBox(height: 28),

                  // Countdown
                  _canResend
                      ? GestureDetector(
                          onTap: _resend,
                          child: Text(
                            'Renvoyer le code',
                            style: GoogleFonts.nunito(
                              color: AppColors.sky2,
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                            ),
                          ),
                        )
                      : RichText(
                          text: TextSpan(
                            style: GoogleFonts.nunito(
                              color: AppColors.muted,
                              fontSize: 14,
                            ),
                            children: [
                              const TextSpan(text: 'Renvoyer dans '),
                              TextSpan(
                                text: _timerDisplay,
                                style: GoogleFonts.nunito(
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.navy,
                                ),
                              ),
                            ],
                          ),
                        ),

                  const SizedBox(height: 36),

                  // Verify button
                  SkyGradientButton(
                    label: 'Vérifier',
                    onPressed: _isLoading ? null : _submit,
                    isLoading: _isLoading,
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
// OTP header
// ---------------------------------------------------------------------------

class _OtpHeader extends StatelessWidget {
  const _OtpHeader({
    required this.subtitle,
    required this.icon,
    required this.onBack,
  });

  final String subtitle;
  final IconData icon;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.fromLTRB(
        20,
        MediaQuery.of(context).padding.top + 16,
        20,
        24,
      ),
      decoration: const BoxDecoration(gradient: AppColors.navyGradient),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GestureDetector(
            onTap: onBack,
            child: Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(30),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(
                Icons.arrow_back_ios_new_rounded,
                color: Colors.white,
                size: 16,
              ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Icon(icon, color: Colors.white.withAlpha(200), size: 22),
              const SizedBox(width: 8),
              Text(
                'Vérification OTP',
                style: GoogleFonts.nunito(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: GoogleFonts.nunito(
              fontSize: 13,
              color: Colors.white.withAlpha(200),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Single OTP box
// ---------------------------------------------------------------------------

class _OtpBox extends StatelessWidget {
  const _OtpBox({
    required this.controller,
    required this.focusNode,
    required this.onChanged,
    required this.onKeyEvent,
    required this.hasError,
  });

  final TextEditingController controller;
  final FocusNode focusNode;
  final ValueChanged<String> onChanged;
  final ValueChanged<KeyEvent> onKeyEvent;
  final bool hasError;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 42,
      height: 52,
      child: KeyboardListener(
        focusNode: FocusNode(),
        onKeyEvent: onKeyEvent,
        child: TextFormField(
          controller: controller,
          focusNode: focusNode,
          onChanged: onChanged,
          keyboardType: TextInputType.number,
          textAlign: TextAlign.center,
          maxLength: 1,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          style: GoogleFonts.nunito(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: AppColors.navy,
          ),
          decoration: InputDecoration(
            counterText: '',
            contentPadding: EdgeInsets.zero,
            filled: true,
            fillColor: AppColors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(
                color: hasError ? AppColors.danger : AppColors.border,
                width: 1.5,
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(
                color: hasError ? AppColors.danger : AppColors.border,
                width: 1.5,
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(
                color: hasError ? AppColors.danger : AppColors.sky,
                width: 2,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
