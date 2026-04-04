import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/services/device_service.dart';
import '../../../../core/services/firebase_phone_auth_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';

/// Registration screen — email or phone + role selection.
///
/// Flow: this screen → `/auth/verify-otp`
class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  String _selectedRole = 'subscriber';
  // ---- Granular consents (C1–C6) ----
  // C1–C4 are mandatory; C5–C6 are optional.
  bool _consentCgu = false;          // C1 — CGU + Privacy Policy
  bool _consentTargeting = false;    // C2 — Targeted ads
  bool _consentTransfer = false;     // C3 — Data transfer to advertisers
  bool _consentFingerprint = false;  // C4 — Device fingerprinting
  bool _consentNotifications = false; // C5 — Push notifications (optional)
  bool _consentMarketing = false;    // C6 — Marketing emails (optional)

  bool _isLoading = false;

  void _openLegal(String path) {
    final base = AppConfig.baseUrl.replaceAll(RegExp(r'/api$'), '');
    launchUrl(Uri.parse('$base$path'), mode: LaunchMode.externalApplication);
  }

  bool get _mandatoryConsentsAccepted =>
      _consentCgu &&
      _consentTargeting &&
      _consentTransfer &&
      _consentFingerprint;

  /// `phone` ou `email`
  String _authMethod = 'phone';

  @override
  void dispose() {
    _phoneController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_mandatoryConsentsAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Veuillez accepter tous les consentements obligatoires',
            style: GoogleFonts.nunito(),
          ),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      if (_authMethod == 'phone') {
        await _submitPhone();
      } else {
        await _submitEmail();
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString(), style: GoogleFonts.nunito()),
          backgroundColor: AppTheme.error,
        ),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  /// Inscription par téléphone.
  ///
  /// Tente Firebase Phone Auth pour envoyer le SMS.
  /// En cas d'échec (ex: Phone Auth non activé), utilise l'OTP backend.
  Future<void> _submitPhone() async {
    final rawPhone = _phoneController.text.trim();
    final fullPhone = '+225$rawPhone';

    // Enregistrer d'abord sur le backend (crée le user + génère OTP backup)
    // Call repository directly to avoid auth state change triggering GoRouter refresh
    await ref.read(authRepositoryProvider).register(
      fullPhone,
      _selectedRole,
      method: 'phone',
      consents: _buildConsentPayload(),
    );

    if (!mounted) return;

    // Tenter Firebase Phone Auth
    final firebaseAuth = ref.read(firebasePhoneAuthProvider);
    final completer = Completer<bool>();

    firebaseAuth.verifyPhoneNumber(
      phoneNumber: fullPhone,
      onCodeSent: (verificationId) {
        if (!completer.isCompleted) completer.complete(true);
        if (!mounted) return;
        context.go(
          '/auth/verify-otp',
          extra: {
            'phone': fullPhone,
            'type': 'registration',
            'method': 'phone',
            'verificationId': verificationId,
          },
        );
      },
      onAutoVerified: (credential) async {
        if (!completer.isCompleted) completer.complete(true);
        if (!mounted) return;
        try {
          final userCred =
              await firebaseAuth.signInWithCredential(credential);
          final idToken = await userCred.user?.getIdToken();
          if (idToken != null && mounted) {
            await ref.read(authProvider.notifier).verifyWithFirebase(
                  phone: fullPhone,
                  firebaseIdToken: idToken,
                  type: 'registration',
                );
            if (!mounted) return;
            final user = ref.read(currentUserProvider);
            if (user != null && user.isSubscriber && user.name == null) {
              context.go('/auth/complete-profile');
            } else {
              context.go('/feed');
            }
          }
        } catch (e) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(e.toString(), style: GoogleFonts.nunito()),
                backgroundColor: AppTheme.error,
              ),
            );
          }
        }
      },
      onError: (error) {
        // Firebase Phone Auth échoue — fallback vers OTP backend
        if (!completer.isCompleted) completer.complete(false);
      },
    );

    final firebaseSucceeded = await completer.future;

    // Fallback : naviguer vers l'écran OTP avec méthode backend
    if (!firebaseSucceeded && mounted) {
      context.go(
        '/auth/verify-otp',
        extra: {
          'phone': fullPhone,
          'type': 'registration',
          'method': 'phone_backend',
        },
      );
    }
  }

  /// Inscription par email via OTP backend.
  Future<void> _submitEmail() async {
    final email = _emailController.text.trim();

    // Call repository directly to avoid auth state change triggering GoRouter refresh
    await ref.read(authRepositoryProvider).register(
      email,
      _selectedRole,
      method: 'email',
      consents: _buildConsentPayload(),
    );

    if (!mounted) return;

    context.go(
      '/auth/verify-otp',
      extra: {
        'email': email,
        'type': 'registration',
        'method': 'email',
      },
    );
  }

  /// Builds the consent flags map to send to the backend on registration.
  Map<String, dynamic> _buildConsentPayload() {
    return {
      'consent_cgu': _consentCgu,
      'consent_targeting': _consentTargeting,
      'consent_transfer': _consentTransfer,
      'consent_fingerprint': _consentFingerprint,
      'consent_notifications': _consentNotifications,
      'consent_marketing': _consentMarketing,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // --- Navy gradient header ---
          _AuthHeader(
            title: 'Créer un compte',
            subtitle: 'Commencez à gagner des FCFA dès aujourd\'hui',
          ),

          // --- Scrollable form body ---
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Auth method toggle
                    _AuthMethodToggle(
                      selected: _authMethod,
                      onChanged: (method) =>
                          setState(() => _authMethod = method),
                    ),

                    const SizedBox(height: 20),

                    // Phone or Email field
                    if (_authMethod == 'phone') ...[
                      _FieldLabel('Numéro de téléphone'),
                      const SizedBox(height: 8),
                      _PhoneField(controller: _phoneController),
                    ] else ...[
                      _FieldLabel('Adresse e-mail'),
                      const SizedBox(height: 8),
                      _EmailField(controller: _emailController),
                    ],

                    const SizedBox(height: 24),

                    // Role selector
                    _FieldLabel('Je suis…'),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: _RoleCard(
                            label: 'Abonné',
                            description: 'Je regarde des pubs et gagne',
                            icon: Icons.play_circle_rounded,
                            isSelected: _selectedRole == 'subscriber',
                            onTap: () =>
                                setState(() => _selectedRole = 'subscriber'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _RoleCard(
                            label: 'Annonceur',
                            description: 'Je diffuse mes publicités',
                            icon: Icons.campaign_rounded,
                            isSelected: _selectedRole == 'advertiser',
                            onTap: () =>
                                setState(() => _selectedRole = 'advertiser'),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 20),

                    // ---- Consentements obligatoires ----
                    _ConsentSectionLabel(
                      label: 'Consentements obligatoires',
                      isRequired: true,
                    ),
                    const SizedBox(height: 10),

                    // C1 — CGU + Privacy
                    _ConsentCheckbox(
                      value: _consentCgu,
                      onChanged: (val) =>
                          setState(() => _consentCgu = val),
                      child: RichText(
                        text: TextSpan(
                          style: GoogleFonts.nunito(
                            fontSize: 12,
                            color: AppColors.muted,
                            height: 1.4,
                          ),
                          children: [
                            const TextSpan(text: 'J\'accepte les '),
                            WidgetSpan(
                              alignment: PlaceholderAlignment.baseline,
                              baseline: TextBaseline.alphabetic,
                              child: GestureDetector(
                                onTap: () => _openLegal('/cgu'),
                                child: Text(
                                  'Conditions Générales d\'Utilisation',
                                  style: GoogleFonts.nunito(
                                    fontSize: 12,
                                    color: AppColors.sky2,
                                    fontWeight: FontWeight.w700,
                                    decoration: TextDecoration.underline,
                                  ),
                                ),
                              ),
                            ),
                            const TextSpan(text: ' et la '),
                            WidgetSpan(
                              alignment: PlaceholderAlignment.baseline,
                              baseline: TextBaseline.alphabetic,
                              child: GestureDetector(
                                onTap: () => _openLegal('/confidentialite'),
                                child: Text(
                                  'Politique de Confidentialité',
                                  style: GoogleFonts.nunito(
                                    fontSize: 12,
                                    color: AppColors.sky2,
                                    fontWeight: FontWeight.w700,
                                    decoration: TextDecoration.underline,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),

                    // C2 — Targeted advertising
                    _ConsentCheckbox(
                      value: _consentTargeting,
                      onChanged: (val) =>
                          setState(() => _consentTargeting = val),
                      child: Text(
                        'J\'accepte de recevoir des publicités ciblées en fonction de mon profil et de mes centres d\'intérêt.',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: AppColors.muted,
                          height: 1.4,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),

                    // C3 — Data transfer to advertisers
                    _ConsentCheckbox(
                      value: _consentTransfer,
                      onChanged: (val) =>
                          setState(() => _consentTransfer = val),
                      child: Text(
                        'J\'autorise le transfert de mes données pseudonymisées aux annonceurs partenaires à des fins de mesure d\'audience.',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: AppColors.muted,
                          height: 1.4,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),

                    // C4 — Device fingerprinting
                    _ConsentCheckbox(
                      value: _consentFingerprint,
                      onChanged: (val) =>
                          setState(() => _consentFingerprint = val),
                      child: Text(
                        'J\'accepte l\'utilisation d\'un identifiant technique de mon appareil (empreinte) pour prévenir la fraude.',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: AppColors.muted,
                          height: 1.4,
                        ),
                      ),
                    ),

                    const SizedBox(height: 18),

                    // ---- Préférences optionnelles ----
                    _ConsentSectionLabel(
                      label: 'Préférences optionnelles',
                      isRequired: false,
                    ),
                    const SizedBox(height: 10),

                    // C5 — Push notifications
                    _ConsentCheckbox(
                      value: _consentNotifications,
                      onChanged: (val) =>
                          setState(() => _consentNotifications = val),
                      child: Text(
                        'Recevoir des notifications push sur les nouvelles publicités disponibles et mes gains.',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: AppColors.muted,
                          height: 1.4,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),

                    // C6 — Marketing emails
                    _ConsentCheckbox(
                      value: _consentMarketing,
                      onChanged: (val) =>
                          setState(() => _consentMarketing = val),
                      child: Text(
                        'Recevoir des e-mails promotionnels et des actualités de oon.click.',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: AppColors.muted,
                          height: 1.4,
                        ),
                      ),
                    ),

                    const SizedBox(height: 28),

                    // Submit — disabled until all mandatory consents are checked
                    SkyGradientButton(
                      label: 'Continuer',
                      onPressed: (_isLoading || !_mandatoryConsentsAccepted)
                          ? null
                          : _submit,
                      isLoading: _isLoading,
                    ),

                    const SizedBox(height: 20),

                    // Divider "OU"
                    Row(
                      children: [
                        const Expanded(
                          child: Divider(color: AppColors.border, thickness: 1),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Text(
                            'OU',
                            style: GoogleFonts.nunito(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: AppColors.muted,
                            ),
                          ),
                        ),
                        const Expanded(
                          child: Divider(color: AppColors.border, thickness: 1),
                        ),
                      ],
                    ),

                    const SizedBox(height: 20),

                    // Google Sign-In button
                    _GoogleSignInButton(
                      isLoading: _isLoading,
                      selectedRole: _selectedRole,
                      onSuccess: () {
                        if (!mounted) return;
                        final user = ref.read(currentUserProvider);
                        if (user != null && user.isSubscriber && user.name == null) {
                          context.go('/auth/complete-profile');
                        } else {
                          context.go('/feed');
                        }
                      },
                      onError: (error) {
                        if (!mounted) return;
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(error, style: GoogleFonts.nunito()),
                            backgroundColor: AppTheme.error,
                          ),
                        );
                      },
                    ),

                    const SizedBox(height: 20),

                    // Login link
                    Center(
                      child: GestureDetector(
                        onTap: () async {
                          final identifier = _authMethod == 'email'
                              ? _emailController.text.trim()
                              : _phoneController.text.trim();
                          if (identifier.isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text(
                                    'Saisissez votre e-mail ou téléphone pour vous connecter.'),
                              ),
                            );
                            return;
                          }
                          try {
                            setState(() => _isLoading = true);
                            await ref
                                .read(authRepositoryProvider)
                                .login(identifier, method: _authMethod);
                            if (!mounted) return;
                            context.push('/auth/verify-otp', extra: {
                              'phone': _authMethod == 'phone' ? identifier : '',
                              'email': _authMethod == 'email' ? identifier : '',
                              'type': 'login',
                              'method': _authMethod == 'phone'
                                  ? 'phone_backend'
                                  : 'email',
                            });
                          } catch (e) {
                            if (!mounted) return;
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(e.toString())),
                            );
                          } finally {
                            if (mounted) setState(() => _isLoading = false);
                          }
                        },
                        child: RichText(
                          text: TextSpan(
                            style: GoogleFonts.nunito(
                              fontSize: 14,
                              color: AppColors.muted,
                            ),
                            children: [
                              const TextSpan(text: 'Déjà inscrit ? '),
                              TextSpan(
                                text: 'Se connecter',
                                style: GoogleFonts.nunito(
                                  color: AppColors.sky2,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
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
// Auth method toggle (Phone / Email)
// ---------------------------------------------------------------------------

class _AuthMethodToggle extends StatelessWidget {
  const _AuthMethodToggle({
    required this.selected,
    required this.onChanged,
  });

  final String selected;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.skyPale,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      padding: const EdgeInsets.all(4),
      child: Row(
        children: [
          _ToggleItem(
            label: 'Téléphone',
            icon: Icons.phone_android_rounded,
            isSelected: selected == 'phone',
            onTap: () => onChanged('phone'),
          ),
          _ToggleItem(
            label: 'E-mail',
            icon: Icons.email_rounded,
            isSelected: selected == 'email',
            onTap: () => onChanged('email'),
          ),
        ],
      ),
    );
  }
}

class _ToggleItem extends StatelessWidget {
  const _ToggleItem({
    required this.label,
    required this.icon,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: isSelected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(9),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: AppColors.sky.withAlpha(40),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 18,
                color: isSelected ? AppColors.sky : AppColors.muted,
              ),
              const SizedBox(width: 6),
              Text(
                label,
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                  color: isSelected ? AppColors.navy : AppColors.muted,
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
// Shared auth header
// ---------------------------------------------------------------------------

/// Navy gradient header used across auth screens.
class _AuthHeader extends StatelessWidget {
  const _AuthHeader({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

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
      decoration: const BoxDecoration(
        gradient: AppColors.navyGradient,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: GoogleFonts.nunito(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: Colors.white.withAlpha(200),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Phone field with CI flag prefix
// ---------------------------------------------------------------------------

class _PhoneField extends StatelessWidget {
  const _PhoneField({required this.controller});

  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType: TextInputType.phone,
      inputFormatters: [
        FilteringTextInputFormatter.digitsOnly,
        LengthLimitingTextInputFormatter(10),
      ],
      style: GoogleFonts.nunito(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: AppColors.navy,
      ),
      decoration: InputDecoration(
        prefixIcon: Container(
          width: 80,
          margin: const EdgeInsets.only(right: 12),
          decoration: const BoxDecoration(
            color: AppColors.skyMid,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(10),
              bottomLeft: Radius.circular(10),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('🇨🇮', style: TextStyle(fontSize: 16)),
              const SizedBox(width: 4),
              Text(
                '+225',
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppColors.navy,
                ),
              ),
            ],
          ),
        ),
        prefixIconConstraints:
            const BoxConstraints(minWidth: 80, minHeight: 44),
        hintText: '07 01 23 45 67',
        hintStyle: GoogleFonts.nunito(color: AppColors.textHint, fontSize: 14),
      ),
      validator: (value) {
        if (value == null || value.trim().isEmpty) {
          return 'Veuillez saisir votre numéro';
        }
        if (value.trim().length < 9) {
          return 'Numéro invalide (9 ou 10 chiffres requis)';
        }
        return null;
      },
    );
  }
}

// ---------------------------------------------------------------------------
// Email field
// ---------------------------------------------------------------------------

class _EmailField extends StatelessWidget {
  const _EmailField({required this.controller});

  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType: TextInputType.emailAddress,
      autocorrect: false,
      style: GoogleFonts.nunito(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: AppColors.navy,
      ),
      decoration: InputDecoration(
        prefixIcon: Container(
          width: 50,
          margin: const EdgeInsets.only(right: 8),
          decoration: const BoxDecoration(
            color: AppColors.skyMid,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(10),
              bottomLeft: Radius.circular(10),
            ),
          ),
          child: const Icon(
            Icons.email_outlined,
            color: AppColors.navy,
            size: 20,
          ),
        ),
        prefixIconConstraints:
            const BoxConstraints(minWidth: 50, minHeight: 44),
        hintText: 'exemple@email.com',
        hintStyle: GoogleFonts.nunito(color: AppColors.textHint, fontSize: 14),
      ),
      validator: (value) {
        if (value == null || value.trim().isEmpty) {
          return 'Veuillez saisir votre adresse e-mail';
        }
        final emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
        if (!emailRegex.hasMatch(value.trim())) {
          return 'Adresse e-mail invalide';
        }
        return null;
      },
    );
  }
}

// ---------------------------------------------------------------------------
// Role card
// ---------------------------------------------------------------------------

class _RoleCard extends StatelessWidget {
  const _RoleCard({
    required this.label,
    required this.description,
    required this.icon,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final String description;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.skyPale : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? AppColors.sky : AppColors.border,
            width: isSelected ? 2 : 1.5,
          ),
        ),
        child: Column(
          children: [
            Icon(
              icon,
              size: 30,
              color: isSelected ? AppColors.sky : AppColors.muted,
            ),
            const SizedBox(height: 8),
            Text(
              label,
              style: GoogleFonts.nunito(
                fontWeight: FontWeight.w700,
                fontSize: 14,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              description,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(
                fontSize: 11,
                color: AppColors.muted,
                height: 1.3,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Field label helper
// ---------------------------------------------------------------------------

class _FieldLabel extends StatelessWidget {
  const _FieldLabel(this.label);

  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: GoogleFonts.nunito(
        fontWeight: FontWeight.w700,
        fontSize: 14,
        color: AppColors.navy,
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Consent section label
// ---------------------------------------------------------------------------

class _ConsentSectionLabel extends StatelessWidget {
  const _ConsentSectionLabel({
    required this.label,
    required this.isRequired,
  });

  final String label;
  final bool isRequired;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          label,
          style: GoogleFonts.nunito(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: AppColors.navy,
            letterSpacing: 0.2,
          ),
        ),
        if (isRequired) ...[
          const SizedBox(width: 4),
          Text(
            '*',
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w900,
              color: AppColors.danger,
            ),
          ),
        ],
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Consent checkbox row
// ---------------------------------------------------------------------------

class _ConsentCheckbox extends StatelessWidget {
  const _ConsentCheckbox({
    required this.value,
    required this.onChanged,
    required this.child,
  });

  final bool value;
  final ValueChanged<bool> onChanged;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => onChanged(!value),
      behavior: HitTestBehavior.opaque,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            width: 20,
            height: 20,
            decoration: BoxDecoration(
              color: value ? AppColors.sky : Colors.white,
              borderRadius: BorderRadius.circular(5),
              border: Border.all(
                color: value ? AppColors.sky : AppColors.border,
                width: 1.5,
              ),
            ),
            child: value
                ? const Icon(Icons.check, color: Colors.white, size: 13)
                : null,
          ),
          const SizedBox(width: 10),
          Expanded(child: child),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Google Sign-In button
// ---------------------------------------------------------------------------

class _GoogleSignInButton extends ConsumerStatefulWidget {
  const _GoogleSignInButton({
    required this.isLoading,
    required this.selectedRole,
    required this.onSuccess,
    required this.onError,
  });

  final bool isLoading;
  final String selectedRole;
  final VoidCallback onSuccess;
  final ValueChanged<String> onError;

  @override
  ConsumerState<_GoogleSignInButton> createState() =>
      _GoogleSignInButtonState();
}

class _GoogleSignInButtonState extends ConsumerState<_GoogleSignInButton> {
  bool _isGoogleLoading = false;

  Future<void> _signInWithGoogle() async {
    if (_isGoogleLoading || widget.isLoading) return;

    setState(() => _isGoogleLoading = true);

    try {
      final fingerprint = await ref.read(deviceFingerprintProvider.future);
      final platform = await ref.read(deviceServiceProvider).getPlatform();

      final success = await ref.read(authProvider.notifier).signInWithGoogle(
            role: widget.selectedRole,
            fingerprint: fingerprint,
            platform: platform,
          );

      if (!mounted) return;

      if (success) {
        widget.onSuccess();
      }
    } catch (e) {
      if (!mounted) return;
      widget.onError(e.toString());
    } finally {
      if (mounted) setState(() => _isGoogleLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: OutlinedButton(
        onPressed: _isGoogleLoading || widget.isLoading
            ? null
            : _signInWithGoogle,
        style: OutlinedButton.styleFrom(
          backgroundColor: Colors.white,
          side: const BorderSide(color: AppColors.border, width: 1.5),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 16),
        ),
        child: _isGoogleLoading
            ? const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  color: AppColors.muted,
                ),
              )
            : Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Google "G" logo
                  SizedBox(
                    width: 20,
                    height: 20,
                    child: CustomPaint(painter: _GoogleLogoPainter()),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    'Continuer avec Google',
                    style: GoogleFonts.nunito(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: AppColors.navy,
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Google "G" logo painter
// ---------------------------------------------------------------------------

class _GoogleLogoPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final double w = size.width;
    final double h = size.height;

    // Blue
    final bluePaint = Paint()..color = const Color(0xFF4285F4);
    // Red
    final redPaint = Paint()..color = const Color(0xFFEA4335);
    // Yellow
    final yellowPaint = Paint()..color = const Color(0xFFFBBC05);
    // Green
    final greenPaint = Paint()..color = const Color(0xFF34A853);

    final center = Offset(w / 2, h / 2);
    final radius = w / 2;

    // Draw colored arcs
    final rect = Rect.fromCircle(center: center, radius: radius);

    // Blue (right side)
    canvas.drawArc(rect, -0.4, 1.2, true, bluePaint);
    // Green (bottom)
    canvas.drawArc(rect, 0.8, 1.0, true, greenPaint);
    // Yellow (left-bottom)
    canvas.drawArc(rect, 1.8, 1.0, true, yellowPaint);
    // Red (top)
    canvas.drawArc(rect, 2.8, 0.9, true, redPaint);

    // White center circle
    final whitePaint = Paint()..color = Colors.white;
    canvas.drawCircle(center, radius * 0.55, whitePaint);

    // Blue bar on the right
    canvas.drawRect(
      Rect.fromLTWH(w * 0.48, h * 0.35, w * 0.52, h * 0.3),
      bluePaint,
    );

    // White rect to cut the bar
    canvas.drawRect(
      Rect.fromLTWH(w * 0.48, h * 0.42, w * 0.32, h * 0.16),
      whitePaint,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
