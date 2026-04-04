import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';

// ---------------------------------------------------------------------------
// Static data
// ---------------------------------------------------------------------------

const _citiesCI = [
  'Abidjan',
  'Bouaké',
  'Daloa',
  'Korhogo',
  'Man',
  'San-Pédro',
  'Yamoussoukro',
  'Gagnoa',
  'Abengourou',
  'Divo',
  'Soubré',
  'Odienné',
  'Bondoukou',
  'Katiola',
  'Ferkessédougou',
];

const _operators = ['MTN', 'Moov', 'Orange'];

const _interests = [
  'Sport',
  'Musique',
  'Mode & Beauté',
  'Alimentation',
  'Technologie',
  'Voyage',
  'Cinéma',
  'Finance',
  'Santé',
  'Éducation',
  'Jeux vidéo',
  'Immobilier',
  'Automobile',
  'Cuisine',
  'Agriculture',
];

// ---------------------------------------------------------------------------
// Screen
// ---------------------------------------------------------------------------

/// Multi-step profile completion screen for subscribers.
///
/// Step 1 — Identity (first name, gender, birth date)
/// Step 2 — Location (city, mobile operator)
/// Step 3 — Preferences (interests, referral code)
class CompleteProfileScreen extends ConsumerStatefulWidget {
  const CompleteProfileScreen({super.key});

  @override
  ConsumerState<CompleteProfileScreen> createState() =>
      _CompleteProfileScreenState();
}

class _CompleteProfileScreenState extends ConsumerState<CompleteProfileScreen>
    with SingleTickerProviderStateMixin {
  final _formKeys = [
    GlobalKey<FormState>(),
    GlobalKey<FormState>(),
    GlobalKey<FormState>(),
  ];

  int _currentStep = 0;

  // Step 1
  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl = TextEditingController();
  String? _gender;
  DateTime? _birthDate;

  // Step 2
  String? _city;
  String? _operator;

  // Step 3
  final Set<String> _selectedInterests = {};
  final _referralCtrl = TextEditingController();

  late final AnimationController _slideCtrl;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _slideCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 300),
    );
    _slideAnimation = Tween<Offset>(
      begin: const Offset(1, 0),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _slideCtrl, curve: Curves.easeOutCubic));
    _slideCtrl.forward();
  }

  @override
  void dispose() {
    _slideCtrl.dispose();
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    _referralCtrl.dispose();
    super.dispose();
  }

  void _nextStep() {
    if (!_formKeys[_currentStep].currentState!.validate()) return;
    if (_currentStep == 2) {
      _submit();
      return;
    }
    _slideCtrl.reset();
    setState(() => _currentStep++);
    _slideCtrl.forward();
  }

  void _prevStep() {
    if (_currentStep == 0) {
      context.pop();
      return;
    }
    _slideCtrl.reset();
    setState(() => _currentStep--);
    _slideCtrl.forward();
  }

  Future<void> _submit() async {
    final payload = {
      'first_name': _firstNameCtrl.text.trim(),
      'last_name': _lastNameCtrl.text.trim(),
      if (_gender != null) 'gender': _gender,
      if (_birthDate != null)
        'birth_date': _birthDate!.toIso8601String().substring(0, 10),
      if (_city != null) 'city': _city,
      if (_operator != null) 'operator': _operator!.toLowerCase(),
      if (_selectedInterests.isNotEmpty)
        'interests': _selectedInterests.toList(),
      if (_referralCtrl.text.trim().isNotEmpty)
        'referral_code': _referralCtrl.text.trim(),
    };

    try {
      await ref.read(authProvider.notifier).completeProfile(payload);
      if (!mounted) return;
      context.go('/feed');
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString(), style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    final isLoading = authState.isLoading;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: EdgeInsets.fromLTRB(
              20,
              MediaQuery.of(context).padding.top + 16,
              20,
              20,
            ),
            decoration: const BoxDecoration(gradient: AppColors.navyGradient),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Back button
                GestureDetector(
                  onTap: _prevStep,
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
                const SizedBox(height: 12),
                Text(
                  'Mon profil',
                  style: GoogleFonts.nunito(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 16),

                // Progress pills
                Row(
                  children: List.generate(3, (i) {
                    final isDone = i <= _currentStep;
                    return Expanded(
                      child: Container(
                        margin: EdgeInsets.only(right: i < 2 ? 8 : 0),
                        height: 6,
                        decoration: BoxDecoration(
                          gradient: isDone
                              ? AppColors.skyGradient
                              : null,
                          color: isDone ? null : Colors.white.withAlpha(50),
                          borderRadius: BorderRadius.circular(4),
                        ),
                      ),
                    );
                  }),
                ),
                const SizedBox(height: 6),
                Text(
                  'Étape ${_currentStep + 1} / 3',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    color: Colors.white.withAlpha(180),
                  ),
                ),
              ],
            ),
          ),

          // Animated step content
          Expanded(
            child: SlideTransition(
              position: _slideAnimation,
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 24, 20, 16),
                child: _buildStepContent(),
              ),
            ),
          ),

          // Bottom action
          Container(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
            color: AppColors.bg,
            child: SkyGradientButton(
              label: _currentStep == 2 ? 'Terminer' : 'Continuer',
              onPressed: isLoading ? null : _nextStep,
              isLoading: isLoading,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepContent() {
    return switch (_currentStep) {
      0 => _buildStep1(),
      1 => _buildStep2(),
      _ => _buildStep3(),
    };
  }

  // ---------------------------------------------------------------------------
  // Step 1 — Identity
  // ---------------------------------------------------------------------------

  Widget _buildStep1() {
    return Form(
      key: _formKeys[0],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StepTitle(
            title: 'Votre identité',
            subtitle: 'Ces informations personnalisent votre expérience.',
          ),
          const SizedBox(height: 20),
          _FieldLabel('Prénom'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _firstNameCtrl,
            textCapitalization: TextCapitalization.words,
            style: GoogleFonts.nunito(color: AppColors.navy, fontWeight: FontWeight.w600),
            decoration: InputDecoration(
              hintText: 'Ex. Konan',
              hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
            ),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
          ),
          const SizedBox(height: 14),
          _FieldLabel('Nom'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _lastNameCtrl,
            textCapitalization: TextCapitalization.words,
            style: GoogleFonts.nunito(color: AppColors.navy, fontWeight: FontWeight.w600),
            decoration: InputDecoration(
              hintText: 'Ex. Kouamé',
              hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
            ),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
          ),
          const SizedBox(height: 14),
          _FieldLabel('Genre'),
          const SizedBox(height: 8),
          Row(
            children: [
              _GenderPill(
                label: 'H',
                fullLabel: 'Homme',
                isSelected: _gender == 'male',
                onTap: () => setState(() => _gender = 'male'),
              ),
              const SizedBox(width: 10),
              _GenderPill(
                label: 'F',
                fullLabel: 'Femme',
                isSelected: _gender == 'female',
                onTap: () => setState(() => _gender = 'female'),
              ),
              const SizedBox(width: 10),
              _GenderPill(
                label: 'Autre',
                fullLabel: 'Autre',
                isSelected: _gender == 'other',
                onTap: () => setState(() => _gender = 'other'),
              ),
            ],
          ),
          const SizedBox(height: 14),
          _FieldLabel('Date de naissance (optionnel)'),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: DateTime(2000),
                firstDate: DateTime(1940),
                lastDate: DateTime.now().subtract(const Duration(days: 365 * 16)),
                builder: (ctx, child) => Theme(
                  data: Theme.of(ctx).copyWith(
                    colorScheme: const ColorScheme.light(primary: AppColors.sky),
                  ),
                  child: child!,
                ),
              );
              if (picked != null) setState(() => _birthDate = picked);
            },
            child: Container(
              height: 48,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(
                color: AppColors.skyPale,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppColors.border, width: 1.5),
              ),
              child: Row(
                children: [
                  const Icon(Icons.calendar_today_outlined,
                      color: AppColors.muted, size: 18),
                  const SizedBox(width: 10),
                  Text(
                    _birthDate == null
                        ? 'Sélectionner une date'
                        : '${_birthDate!.day.toString().padLeft(2, '0')}/'
                            '${_birthDate!.month.toString().padLeft(2, '0')}/'
                            '${_birthDate!.year}',
                    style: GoogleFonts.nunito(
                      color: _birthDate == null
                          ? AppColors.textHint
                          : AppColors.navy,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Step 2 — Location
  // ---------------------------------------------------------------------------

  Widget _buildStep2() {
    return Form(
      key: _formKeys[1],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StepTitle(
            title: 'Votre localisation',
            subtitle: 'Recevez des pubs adaptées à votre région.',
          ),
          const SizedBox(height: 20),
          _FieldLabel('Ville'),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            value: _city,
            style: GoogleFonts.nunito(
              color: AppColors.navy,
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
            decoration: InputDecoration(
              hintText: 'Choisir une ville',
              hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
            ),
            items: _citiesCI
                .map((c) => DropdownMenuItem(value: c, child: Text(c)))
                .toList(),
            onChanged: (v) => setState(() => _city = v),
            validator: (v) => v == null ? 'Veuillez choisir une ville' : null,
          ),
          const SizedBox(height: 16),
          _FieldLabel('Opérateur mobile'),
          const SizedBox(height: 8),
          Row(
            children: _operators
                .map(
                  (op) => Padding(
                    padding: const EdgeInsets.only(right: 10),
                    child: _OperatorChip(
                      label: op,
                      isSelected: _operator == op,
                      onTap: () => setState(() => _operator = op),
                    ),
                  ),
                )
                .toList(),
          ),
          if (_operator == null)
            Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Text(
                'Veuillez sélectionner un opérateur',
                style: GoogleFonts.nunito(
                  color: AppColors.danger,
                  fontSize: 12,
                ),
              ),
            ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Step 3 — Preferences
  // ---------------------------------------------------------------------------

  Widget _buildStep3() {
    return Form(
      key: _formKeys[2],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StepTitle(
            title: 'Vos centres d\'intérêts',
            subtitle:
                'Sélectionnez au moins un domaine pour recevoir des pubs '
                'qui vous correspondent.',
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _interests.map((interest) {
              final isSelected = _selectedInterests.contains(interest);
              return GestureDetector(
                onTap: () => setState(() {
                  if (isSelected) {
                    _selectedInterests.remove(interest);
                  } else {
                    _selectedInterests.add(interest);
                  }
                }),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: isSelected ? AppColors.skyPale : Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: isSelected ? AppColors.sky : AppColors.border,
                      width: isSelected ? 2 : 1.5,
                    ),
                  ),
                  child: Text(
                    interest,
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      fontWeight:
                          isSelected ? FontWeight.w700 : FontWeight.w500,
                      color: isSelected ? AppColors.sky : AppColors.muted,
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),
          _FieldLabel('Code de parrainage (optionnel)'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _referralCtrl,
            textCapitalization: TextCapitalization.characters,
            style: GoogleFonts.nunito(
                color: AppColors.navy, fontWeight: FontWeight.w700),
            decoration: InputDecoration(
              hintText: 'Ex. OON-XXXX',
              hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
              prefixIcon: const Icon(Icons.card_giftcard_outlined,
                  color: AppColors.sky, size: 20),
            ),
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Helper widgets
// ---------------------------------------------------------------------------

class _StepTitle extends StatelessWidget {
  const _StepTitle({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: GoogleFonts.nunito(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: AppColors.navy,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: GoogleFonts.nunito(
            fontSize: 13,
            color: AppColors.muted,
            height: 1.5,
          ),
        ),
      ],
    );
  }
}

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

class _GenderPill extends StatelessWidget {
  const _GenderPill({
    required this.label,
    required this.fullLabel,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final String fullLabel;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.skyPale : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? AppColors.sky : AppColors.border,
            width: isSelected ? 2 : 1.5,
          ),
        ),
        child: Text(
          fullLabel,
          style: GoogleFonts.nunito(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: isSelected ? AppColors.sky : AppColors.muted,
          ),
        ),
      ),
    );
  }
}

class _OperatorChip extends StatelessWidget {
  const _OperatorChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.skyPale : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? AppColors.sky : AppColors.border,
            width: isSelected ? 2 : 1.5,
          ),
        ),
        child: Text(
          label,
          style: GoogleFonts.nunito(
            fontWeight: FontWeight.w700,
            fontSize: 14,
            color: isSelected ? AppColors.sky : AppColors.muted,
          ),
        ),
      ),
    );
  }
}
