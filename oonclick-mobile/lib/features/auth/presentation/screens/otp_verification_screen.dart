import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/services/device_service.dart';
import '../providers/auth_provider.dart';

/// OTP verification screen.
///
/// Receives [phone] and [type] (`register` | `login`) from route extras.
/// Auto-submits when all 6 digits are filled.
class OtpVerificationScreen extends ConsumerStatefulWidget {
  const OtpVerificationScreen({
    super.key,
    required this.phone,
    required this.type,
  });

  final String phone;

  /// `register` or `login`
  final String type;

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

  @override
  void initState() {
    super.initState();
    _startTimer();
    // Auto-focus first field.
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

  // ---------------------------------------------------------------------------
  // OTP input helpers
  // ---------------------------------------------------------------------------

  String get _currentCode =>
      _controllers.map((c) => c.text).join();

  void _onDigitChanged(int index, String value) {
    if (value.length > 1) {
      // Paste scenario — distribute digits.
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

    // Auto-submit when all fields are filled.
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
      final fingerprint =
          await ref.read(deviceFingerprintProvider.future);
      final platform =
          await ref.read(deviceServiceProvider).getPlatform();

      final success = await ref.read(authProvider.notifier).verifyOtp(
            phone: widget.phone,
            code: code,
            type: widget.type,
            fingerprint: fingerprint,
            platform: platform,
          );

      if (!mounted) return;

      if (success) {
        final user = ref.read(currentUserProvider);
        if (user != null && user.isSubscriber && user.name == null) {
          context.go('/auth/complete-profile');
        } else {
          context.go('/feed');
        }
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
        // Clear fields on error.
        for (final c in _controllers) {
          c.clear();
        }
        _focusNodes.first.requestFocus();
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _resend() async {
    if (!_canResend) return;
    try {
      await ref
          .read(authRepositoryProvider)
          .resendOtp(widget.phone, widget.type);
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
          backgroundColor: AppTheme.error,
        ),
      );
    }
  }

  // ---------------------------------------------------------------------------
  // Build
  // ---------------------------------------------------------------------------

  String get _maskedPhone {
    // "+225 07 01 23 45 67" → "+225 XX XXX XX XX"
    if (widget.phone.length < 4) return widget.phone;
    final prefix = widget.phone.substring(0, 4); // +225
    final rest = widget.phone.substring(4);
    if (rest.length < 8) return widget.phone;
    return '$prefix XX XXX ${rest.substring(rest.length - 4, rest.length - 2)} '
        '${rest.substring(rest.length - 2)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.bgPage,
      appBar: AppBar(
        title: const Text('Vérification'),
        leading: BackButton(onPressed: () => context.pop()),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const SizedBox(height: 40),

              // Icon
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: AppTheme.primary.withAlpha(20),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.sms_outlined,
                  color: AppTheme.primary,
                  size: 32,
                ),
              ),
              const SizedBox(height: 20),

              Text(
                'Entrez votre code',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: AppTheme.textPrimary,
                    ),
              ),
              const SizedBox(height: 8),
              Text(
                'Un SMS a été envoyé au',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: AppTheme.textSecondary,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                _maskedPhone,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: AppTheme.textPrimary,
                    ),
              ),

              const SizedBox(height: 36),

              // OTP fields
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(_codeLength, (index) {
                  return Padding(
                    padding: EdgeInsets.only(
                        right: index < _codeLength - 1 ? 10 : 0),
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

              // Inline error
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(
                  _error!,
                  style: const TextStyle(
                    color: AppTheme.error,
                    fontSize: 13,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],

              const SizedBox(height: 32),

              // Submit button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _submit,
                  child: _isLoading
                      ? const SizedBox(
                          height: 22,
                          width: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Valider'),
                ),
              ),

              const SizedBox(height: 20),

              // Resend row
              _canResend
                  ? GestureDetector(
                      onTap: _resend,
                      child: const Text(
                        'Renvoyer le code',
                        style: TextStyle(
                          color: AppTheme.primary,
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                    )
                  : Text(
                      'Renvoyer dans $_secondsLeft s',
                      style: const TextStyle(
                        color: AppTheme.textSecondary,
                        fontSize: 14,
                      ),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Single OTP digit box
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
      width: 48,
      height: 58,
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
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w700,
            color: AppTheme.textPrimary,
          ),
          decoration: InputDecoration(
            counterText: '',
            contentPadding: EdgeInsets.zero,
            filled: true,
            fillColor: AppTheme.bgCard,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: hasError ? AppTheme.error : AppTheme.divider,
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: hasError ? AppTheme.error : AppTheme.divider,
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: hasError ? AppTheme.error : AppTheme.primary,
                width: 2,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
