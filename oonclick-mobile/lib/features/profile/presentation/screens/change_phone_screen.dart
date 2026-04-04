import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../auth/presentation/providers/auth_provider.dart';

/// Écran de modification du numéro de téléphone (2 étapes).
///
/// Étape 1 : saisir le nouveau numéro → POST /auth/change-phone
/// Étape 2 : saisir le code OTP reçu → POST /auth/confirm-phone-change
class ChangePhoneScreen extends ConsumerStatefulWidget {
  const ChangePhoneScreen({super.key});

  @override
  ConsumerState<ChangePhoneScreen> createState() =>
      _ChangePhoneScreenState();
}

class _ChangePhoneScreenState extends ConsumerState<ChangePhoneScreen> {
  int _step = 1;
  bool _isLoading = false;
  String _newPhone = '';

  final _phoneCtrl = TextEditingController();
  final _otpCtrl = TextEditingController();
  final _phoneFocus = FocusNode();
  final _otpFocus = FocusNode();

  @override
  void dispose() {
    _phoneCtrl.dispose();
    _otpCtrl.dispose();
    _phoneFocus.dispose();
    _otpFocus.dispose();
    super.dispose();
  }

  // ---------------------------------------------------------------------------
  // Step 1 — request phone change
  // ---------------------------------------------------------------------------

  Future<void> _requestChange() async {
    final phone = _phoneCtrl.text.trim();
    if (phone.isEmpty) return;

    setState(() => _isLoading = true);

    try {
      final api = ref.read(apiClientProvider);
      await api.post<Map<String, dynamic>>('/auth/change-phone', data: {
        'new_phone': phone,
      });

      setState(() {
        _newPhone = phone;
        _step = 2;
        _isLoading = false;
      });

      // Focus OTP field.
      Future.delayed(const Duration(milliseconds: 300), () {
        if (mounted) _otpFocus.requestFocus();
      });
    } on DioException catch (e) {
      final ex = ApiException.fromDioError(e);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(ex.message, style: GoogleFonts.nunito()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
      setState(() => _isLoading = false);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                e.toString().replaceAll('Exception: ', ''),
                style: GoogleFonts.nunito()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
      setState(() => _isLoading = false);
    }
  }

  // ---------------------------------------------------------------------------
  // Step 2 — confirm OTP
  // ---------------------------------------------------------------------------

  Future<void> _confirmChange() async {
    final code = _otpCtrl.text.trim();
    if (code.isEmpty) return;

    setState(() => _isLoading = true);

    try {
      final api = ref.read(apiClientProvider);
      await api.post<Map<String, dynamic>>(
          '/auth/confirm-phone-change',
          data: {
            'phone': _newPhone,
            'code': code,
          });

      // Refresh user profile to reflect new phone.
      await ref.read(authProvider.notifier).completeProfile({});

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Numéro de téléphone modifié avec succès !',
              style: GoogleFonts.nunito(),
            ),
            backgroundColor: AppColors.success,
          ),
        );
        context.pop();
      }
    } on DioException catch (e) {
      final ex = ApiException.fromDioError(e);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(ex.message, style: GoogleFonts.nunito()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
      setState(() => _isLoading = false);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                e.toString().replaceAll('Exception: ', ''),
                style: GoogleFonts.nunito()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _ChangePhoneTopBar(step: _step),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 32, 24, 40),
              child: _step == 1
                  ? _StepOne(
                      controller: _phoneCtrl,
                      focus: _phoneFocus,
                      isLoading: _isLoading,
                      onNext: _requestChange,
                    )
                  : _StepTwo(
                      newPhone: _newPhone,
                      controller: _otpCtrl,
                      focus: _otpFocus,
                      isLoading: _isLoading,
                      onConfirm: _confirmChange,
                      onBack: () =>
                          setState(() {
                            _step = 1;
                            _otpCtrl.clear();
                          }),
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

class _ChangePhoneTopBar extends StatelessWidget {
  const _ChangePhoneTopBar({required this.step});

  final int step;

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
          Expanded(
            child: Text(
              'Changer de numéro',
              style: GoogleFonts.nunito(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
            ),
          ),
          // Step indicator
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.white.withAlpha(30),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              'Étape $step / 2',
              style: GoogleFonts.nunito(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Step 1 — enter new phone
// ---------------------------------------------------------------------------

class _StepOne extends StatelessWidget {
  const _StepOne({
    required this.controller,
    required this.focus,
    required this.isLoading,
    required this.onNext,
  });

  final TextEditingController controller;
  final FocusNode focus;
  final bool isLoading;
  final VoidCallback onNext;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('📱', style: TextStyle(fontSize: 40)),
        const SizedBox(height: 16),
        Text(
          'Nouveau numéro',
          style: GoogleFonts.nunito(
            fontSize: 22,
            fontWeight: FontWeight.w900,
            color: AppColors.navy,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Entrez votre nouveau numéro de téléphone. Un code de vérification vous sera envoyé.',
          style: GoogleFonts.nunito(
            fontSize: 14,
            color: AppColors.muted,
          ),
        ),
        const SizedBox(height: 28),
        TextFormField(
          controller: controller,
          focusNode: focus,
          keyboardType: TextInputType.phone,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          autofocus: true,
          style: GoogleFonts.nunito(
            fontSize: 16,
            color: AppColors.navy,
            fontWeight: FontWeight.w700,
          ),
          decoration: InputDecoration(
            labelText: 'Numéro de téléphone',
            hintText: 'Ex : 0701234567',
            prefixIcon: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              child: Text(
                '+225',
                style: GoogleFonts.nunito(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: AppColors.navy,
                ),
              ),
            ),
          ),
        ),
        const SizedBox(height: 32),
        SkyGradientButton(
          label: 'Envoyer le code',
          onPressed: isLoading ? null : onNext,
          isLoading: isLoading,
          height: 52,
          borderRadius: 14,
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Step 2 — enter OTP
// ---------------------------------------------------------------------------

class _StepTwo extends StatelessWidget {
  const _StepTwo({
    required this.newPhone,
    required this.controller,
    required this.focus,
    required this.isLoading,
    required this.onConfirm,
    required this.onBack,
  });

  final String newPhone;
  final TextEditingController controller;
  final FocusNode focus;
  final bool isLoading;
  final VoidCallback onConfirm;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('🔐', style: TextStyle(fontSize: 40)),
        const SizedBox(height: 16),
        Text(
          'Code de vérification',
          style: GoogleFonts.nunito(
            fontSize: 22,
            fontWeight: FontWeight.w900,
            color: AppColors.navy,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Un code à 6 chiffres a été envoyé au $newPhone. Entrez-le ci-dessous.',
          style: GoogleFonts.nunito(
            fontSize: 14,
            color: AppColors.muted,
          ),
        ),
        const SizedBox(height: 28),
        TextFormField(
          controller: controller,
          focusNode: focus,
          keyboardType: TextInputType.number,
          inputFormatters: [
            FilteringTextInputFormatter.digitsOnly,
            LengthLimitingTextInputFormatter(6),
          ],
          autofocus: true,
          textAlign: TextAlign.center,
          style: GoogleFonts.nunito(
            fontSize: 28,
            letterSpacing: 10,
            color: AppColors.navy,
            fontWeight: FontWeight.w900,
          ),
          decoration: const InputDecoration(
            hintText: '000000',
          ),
        ),
        const SizedBox(height: 32),
        SkyGradientButton(
          label: 'Confirmer le changement',
          onPressed: isLoading ? null : onConfirm,
          isLoading: isLoading,
          height: 52,
          borderRadius: 14,
        ),
        const SizedBox(height: 16),
        Center(
          child: TextButton.icon(
            onPressed: onBack,
            icon: const Icon(Icons.arrow_back_rounded, size: 16),
            label: Text(
              'Changer le numéro saisi',
              style: GoogleFonts.nunito(fontWeight: FontWeight.w600),
            ),
          ),
        ),
      ],
    );
  }
}
