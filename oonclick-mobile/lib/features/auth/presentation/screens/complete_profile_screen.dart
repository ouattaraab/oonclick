import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';

// ---------------------------------------------------------------------------
// Static reference data for Ivory Coast
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

/// Multi-step profile completion screen for subscribers.
///
/// Step 1 — Identity (first name, last name, gender, birth date)
/// Step 2 — Location (city, mobile operator)
/// Step 3 — Preferences (interests, referral code)
class CompleteProfileScreen extends ConsumerStatefulWidget {
  const CompleteProfileScreen({super.key});

  @override
  ConsumerState<CompleteProfileScreen> createState() =>
      _CompleteProfileScreenState();
}

class _CompleteProfileScreenState
    extends ConsumerState<CompleteProfileScreen>
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

  // ---------------------------------------------------------------------------
  // Navigation between steps
  // ---------------------------------------------------------------------------

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

  // ---------------------------------------------------------------------------
  // Submit
  // ---------------------------------------------------------------------------

  Future<void> _submit() async {
    final payload = {
      'first_name': _firstNameCtrl.text.trim(),
      'last_name': _lastNameCtrl.text.trim(),
      if (_gender != null) 'gender': _gender,
      if (_birthDate != null)
        'birth_date': _birthDate!.toIso8601String().substring(0, 10),
      if (_city != null) 'city': _city,
      if (_operator != null) 'operator': _operator,
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
          content: Text(e.toString()),
          backgroundColor: AppTheme.error,
        ),
      );
    }
  }

  // ---------------------------------------------------------------------------
  // Build
  // ---------------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    final isLoading = authState.isLoading;

    return Scaffold(
      backgroundColor: AppTheme.bgPage,
      appBar: AppBar(
        title: const Text('Mon profil'),
        leading: BackButton(onPressed: _prevStep),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Progress indicator
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              child: _StepProgressBar(
                totalSteps: 3,
                currentStep: _currentStep,
              ),
            ),

            // Animated step content
            Expanded(
              child: SlideTransition(
                position: _slideAnimation,
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: _buildStepContent(),
                ),
              ),
            ),

            // Bottom action button
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 0, 24, 24),
              child: ElevatedButton(
                onPressed: isLoading ? null : _nextStep,
                child: isLoading
                    ? const SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.5,
                          color: Colors.white,
                        ),
                      )
                    : Text(_currentStep == 2 ? 'Terminer' : 'Suivant'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Step content builders
  // ---------------------------------------------------------------------------

  Widget _buildStepContent() {
    return switch (_currentStep) {
      0 => _buildStep1(),
      1 => _buildStep2(),
      _ => _buildStep3(),
    };
  }

  // Step 1 — Identity
  Widget _buildStep1() {
    return Form(
      key: _formKeys[0],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StepTitle(
            step: 1,
            title: 'Votre identité',
            subtitle: 'Ces informations nous permettent de personnaliser '
                'votre expérience.',
          ),
          const SizedBox(height: 24),
          _FieldLabel('Prénom'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _firstNameCtrl,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(hintText: 'Ex. Konan'),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
          ),
          const SizedBox(height: 16),
          _FieldLabel('Nom'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _lastNameCtrl,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(hintText: 'Ex. Kouamé'),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
          ),
          const SizedBox(height: 16),
          _FieldLabel('Genre'),
          const SizedBox(height: 8),
          Row(
            children: [
              _GenderChip(
                label: 'Homme',
                icon: Icons.male_rounded,
                isSelected: _gender == 'male',
                onTap: () => setState(() => _gender = 'male'),
              ),
              const SizedBox(width: 10),
              _GenderChip(
                label: 'Femme',
                icon: Icons.female_rounded,
                isSelected: _gender == 'female',
                onTap: () => setState(() => _gender = 'female'),
              ),
              const SizedBox(width: 10),
              _GenderChip(
                label: 'Autre',
                icon: Icons.person_outline_rounded,
                isSelected: _gender == 'other',
                onTap: () => setState(() => _gender = 'other'),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _FieldLabel('Date de naissance (optionnel)'),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: DateTime(2000),
                firstDate: DateTime(1940),
                lastDate: DateTime.now().subtract(
                  const Duration(days: 365 * 16),
                ),
              );
              if (picked != null) setState(() => _birthDate = picked);
            },
            child: Container(
              height: 52,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: AppTheme.bgCard,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppTheme.divider),
              ),
              child: Row(
                children: [
                  const Icon(Icons.calendar_today_outlined,
                      color: AppTheme.textSecondary, size: 18),
                  const SizedBox(width: 10),
                  Text(
                    _birthDate == null
                        ? 'Sélectionner une date'
                        : '${_birthDate!.day.toString().padLeft(2, '0')}/'
                            '${_birthDate!.month.toString().padLeft(2, '0')}/'
                            '${_birthDate!.year}',
                    style: TextStyle(
                      color: _birthDate == null
                          ? AppTheme.textHint
                          : AppTheme.textPrimary,
                      fontSize: 15,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  // Step 2 — Location
  Widget _buildStep2() {
    return Form(
      key: _formKeys[1],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StepTitle(
            step: 2,
            title: 'Votre localisation',
            subtitle: 'Recevez des pubs adaptées à votre région.',
          ),
          const SizedBox(height: 24),
          _FieldLabel('Ville'),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            initialValue: _city,
            decoration: const InputDecoration(hintText: 'Choisir une ville'),
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
            const Padding(
              padding: EdgeInsets.only(top: 6),
              child: Text(
                'Veuillez sélectionner un opérateur',
                style: TextStyle(color: AppTheme.error, fontSize: 12),
              ),
            ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  // Step 3 — Preferences
  Widget _buildStep3() {
    return Form(
      key: _formKeys[2],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StepTitle(
            step: 3,
            title: 'Vos centres d\'intérêts',
            subtitle:
                'Sélectionnez au moins un domaine pour recevoir des pubs '
                'qui vous correspondent.',
          ),
          const SizedBox(height: 20),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _interests.map((interest) {
              final isSelected = _selectedInterests.contains(interest);
              return FilterChip(
                label: Text(interest),
                selected: isSelected,
                onSelected: (_) => setState(() {
                  if (isSelected) {
                    _selectedInterests.remove(interest);
                  } else {
                    _selectedInterests.add(interest);
                  }
                }),
                selectedColor: AppTheme.primary.withAlpha(30),
                checkmarkColor: AppTheme.primary,
                labelStyle: TextStyle(
                  color: isSelected
                      ? AppTheme.primary
                      : AppTheme.textSecondary,
                  fontWeight: isSelected
                      ? FontWeight.w600
                      : FontWeight.normal,
                ),
                side: BorderSide(
                  color:
                      isSelected ? AppTheme.primary : AppTheme.divider,
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 24),
          _FieldLabel('Code de parrainage (optionnel)'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _referralCtrl,
            textCapitalization: TextCapitalization.characters,
            decoration: const InputDecoration(
              hintText: 'Ex. OON-XXXX',
              prefixIcon:
                  Icon(Icons.card_giftcard_outlined, color: AppTheme.primary),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Helper widgets
// ---------------------------------------------------------------------------

class _StepProgressBar extends StatelessWidget {
  const _StepProgressBar({
    required this.totalSteps,
    required this.currentStep,
  });

  final int totalSteps;
  final int currentStep;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(totalSteps, (i) {
        final isDone = i <= currentStep;
        return Expanded(
          child: Container(
            margin: EdgeInsets.only(right: i < totalSteps - 1 ? 6 : 0),
            height: 5,
            decoration: BoxDecoration(
              color: isDone ? AppTheme.primary : AppTheme.divider,
              borderRadius: BorderRadius.circular(4),
            ),
          ),
        );
      }),
    );
  }
}

class _StepTitle extends StatelessWidget {
  const _StepTitle({
    required this.step,
    required this.title,
    required this.subtitle,
  });

  final int step;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Étape $step / 3',
          style: const TextStyle(
            color: AppTheme.primary,
            fontWeight: FontWeight.w600,
            fontSize: 13,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          title,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w800,
                color: AppTheme.textPrimary,
              ),
        ),
        const SizedBox(height: 6),
        Text(
          subtitle,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppTheme.textSecondary,
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
      style: const TextStyle(
        fontWeight: FontWeight.w600,
        fontSize: 14,
        color: AppTheme.textPrimary,
      ),
    );
  }
}

class _GenderChip extends StatelessWidget {
  const _GenderChip({
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
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primary.withAlpha(20) : AppTheme.bgCard,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? AppTheme.primary : AppTheme.divider,
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon,
                size: 16,
                color: isSelected
                    ? AppTheme.primary
                    : AppTheme.textSecondary),
            const SizedBox(width: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isSelected
                    ? AppTheme.primary
                    : AppTheme.textSecondary,
              ),
            ),
          ],
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
          color:
              isSelected ? AppTheme.primary.withAlpha(20) : AppTheme.bgCard,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? AppTheme.primary : AppTheme.divider,
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontWeight: FontWeight.w700,
            fontSize: 14,
            color: isSelected ? AppTheme.primary : AppTheme.textSecondary,
          ),
        ),
      ),
    );
  }
}
