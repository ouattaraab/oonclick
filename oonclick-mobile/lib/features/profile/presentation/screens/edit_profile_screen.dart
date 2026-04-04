import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/config/platform_config_model.dart';
import '../../../../core/config/platform_config_provider.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../auth/data/models/user_model.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/repositories/profile_repository.dart';
import '../providers/profile_provider.dart';

/// Écran de modification du profil utilisateur.
///
/// Pré-remplit les champs avec les données existantes.
/// Envoie les modifications via PATCH /auth/profile.
/// Gère l'upload de l'avatar via POST /auth/avatar.
class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() =>
      _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _firstNameCtrl;
  late final TextEditingController _lastNameCtrl;
  late final TextEditingController _cityCtrl;

  String? _selectedOperator;
  List<String> _selectedInterests = [];

  /// Valeurs des critères d'audience dynamiques requis pour le profil.
  /// Clé = criterion.name, valeur = dynamic (String, List<String>, bool).
  final Map<String, dynamic> _customFields = {};

  File? _avatarFile;
  bool _isLoading = false;
  bool _isLoadingProfile = true;
  bool _initialized = false;

  static const _operators = ['MTN', 'Moov', 'Orange'];
  static const _operatorApiValues = {'MTN': 'mtn', 'Moov': 'moov', 'Orange': 'orange'};
  static const _operatorDisplayValues = {'mtn': 'MTN', 'moov': 'Moov', 'orange': 'Orange'};
  static const _allInterests = [
    'Sport',
    'Musique',
    'Mode',
    'Tech',
    'Food',
    'Voyage',
    'Finance',
    'Gaming',
    'Santé',
    'Education',
  ];

  @override
  void initState() {
    super.initState();
    _firstNameCtrl = TextEditingController();
    _lastNameCtrl = TextEditingController();
    _cityCtrl = TextEditingController();
  }

  @override
  void dispose() {
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    _cityCtrl.dispose();
    super.dispose();
  }

  void _initFromUser(UserModel user) {
    if (_initialized) return;
    _initialized = true;

    final nameParts = (user.name ?? '').trim().split(RegExp(r'\s+'));
    _firstNameCtrl.text = nameParts.isNotEmpty ? nameParts[0] : '';
    _lastNameCtrl.text = nameParts.length > 1
        ? nameParts.sublist(1).join(' ')
        : '';

    // Charger les données du profil subscriber depuis l'API.
    _loadProfileData();
  }

  Future<void> _loadProfileData() async {
    try {
      final repo = ref.read(profileRepositoryProvider);
      final profile = await repo.getProfileData();

      if (!mounted) return;

      setState(() {
        // Pré-remplir les champs depuis le profil API.
        final firstName = profile['first_name'] as String?;
        final lastName = profile['last_name'] as String?;
        if (firstName != null && firstName.isNotEmpty) {
          _firstNameCtrl.text = firstName;
        }
        if (lastName != null && lastName.isNotEmpty) {
          _lastNameCtrl.text = lastName;
        }

        final city = profile['city'] as String?;
        if (city != null && city.isNotEmpty) {
          _cityCtrl.text = city;
        }

        final operator = profile['operator'] as String?;
        if (operator != null) {
          _selectedOperator = _operatorDisplayValues[operator.toLowerCase()] ?? operator;
        }

        final interests = profile['interests'];
        if (interests is List) {
          _selectedInterests = interests.cast<String>();
        }

        // Pré-remplir les champs personnalisés depuis le profil API.
        final customFields = profile['custom_fields'];
        if (customFields is Map<String, dynamic>) {
          _customFields.addAll(customFields);
        }

        _isLoadingProfile = false;
      });
    } catch (_) {
      if (mounted) setState(() => _isLoadingProfile = false);
    }
  }

  Future<void> _pickAvatar() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 512,
      maxHeight: 512,
    );
    if (picked != null && mounted) {
      setState(() => _avatarFile = File(picked.path));
    }
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _isLoading = true);

    try {
      final repo = ref.read(profileRepositoryProvider);

      // Build profile data with field names matching backend validation.
      final firstName = _firstNameCtrl.text.trim();
      final lastName = _lastNameCtrl.text.trim();

      final data = <String, dynamic>{
        if (firstName.isNotEmpty) 'first_name': firstName,
        if (lastName.isNotEmpty) 'last_name': lastName,
        if (_cityCtrl.text.trim().isNotEmpty) 'city': _cityCtrl.text.trim(),
        if (_selectedOperator != null)
          'operator': _operatorApiValues[_selectedOperator] ?? _selectedOperator!.toLowerCase(),
        if (_selectedInterests.isNotEmpty) 'interests': _selectedInterests,
        if (_customFields.isNotEmpty) 'custom_fields': Map<String, dynamic>.from(_customFields),
      };

      // Upload avatar if selected.
      if (_avatarFile != null) {
        await repo.uploadAvatar(_avatarFile!);
      }

      // Update profile fields.
      await repo.updateProfile(data);

      // Refresh auth state.
      await ref.read(profileProvider.notifier).refresh();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Profil mis à jour avec succès !',
              style: GoogleFonts.nunito(),
            ),
            backgroundColor: AppColors.success,
          ),
        );
        context.pop();
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
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final userAsync = ref.watch(profileProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: userAsync.when(
        loading: () => const Center(
          child: CircularProgressIndicator(color: AppColors.sky),
        ),
        error: (e, _) => Center(
          child: Text(e.toString(),
              style: GoogleFonts.nunito(color: AppColors.danger)),
        ),
        data: (user) {
          if (user == null) {
            return Center(
              child: Text('Utilisateur introuvable.',
                  style: GoogleFonts.nunito(color: AppColors.muted)),
            );
          }
          _initFromUser(user);

          return Column(
            children: [
              _EditTopBar(onSave: _isLoading ? null : _save, isLoading: _isLoading),
              Expanded(
                child: Form(
                  key: _formKey,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 24, 16, 40),
                    children: [
                      // Avatar picker
                      Center(child: _AvatarPicker(
                        user: user,
                        file: _avatarFile,
                        onTap: _pickAvatar,
                      )),
                      const SizedBox(height: 28),

                      // Section: Identité
                      _SectionLabel(label: 'Identité'),
                      const SizedBox(height: 10),
                      _FormField(
                        controller: _firstNameCtrl,
                        label: 'Prénom',
                        hint: 'Votre prénom',
                        validator: (v) =>
                            (v == null || v.trim().isEmpty)
                                ? 'Champ requis'
                                : null,
                      ),
                      const SizedBox(height: 12),
                      _FormField(
                        controller: _lastNameCtrl,
                        label: 'Nom de famille',
                        hint: 'Votre nom',
                      ),
                      const SizedBox(height: 12),
                      _FormField(
                        controller: _cityCtrl,
                        label: 'Ville',
                        hint: 'Ex : Abidjan, Bouaké…',
                      ),
                      const SizedBox(height: 20),

                      // Section: Opérateur
                      _SectionLabel(label: 'Opérateur mobile'),
                      const SizedBox(height: 10),
                      _OperatorPicker(
                        selected: _selectedOperator,
                        operators: _operators,
                        onChanged: (op) =>
                            setState(() => _selectedOperator = op),
                      ),
                      const SizedBox(height: 20),

                      // Section: Intérêts
                      _SectionLabel(label: 'Centres d\'intérêt'),
                      const SizedBox(height: 4),
                      Text(
                        'Sélectionnez pour recevoir des publicités ciblées',
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: AppColors.muted,
                        ),
                      ),
                      const SizedBox(height: 10),
                      _InterestsPicker(
                        selected: _selectedInterests,
                        all: _allInterests,
                        onChanged: (interests) =>
                            setState(() => _selectedInterests = interests),
                      ),
                      const SizedBox(height: 20),

                      // Section: Informations complémentaires (critères dynamiques)
                      _ProfileDynamicCriteriaSection(
                        customFields: _customFields,
                        onChanged: (name, value) {
                          setState(() => _customFields[name] = value);
                        },
                      ),

                      const SizedBox(height: 28),

                      // Save button
                      SkyGradientButton(
                        label: 'Enregistrer les modifications',
                        onPressed: _isLoading ? null : _save,
                        isLoading: _isLoading,
                        height: 52,
                        borderRadius: 14,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _EditTopBar extends StatelessWidget {
  const _EditTopBar({required this.onSave, required this.isLoading});

  final VoidCallback? onSave;
  final bool isLoading;

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
          Text(
            'Modifier le profil',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const Spacer(),
          if (isLoading)
            const SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(
                  strokeWidth: 2, color: Colors.white),
            )
          else
            GestureDetector(
              onTap: onSave,
              child: Text(
                'Sauvegarder',
                style: GoogleFonts.nunito(
                  fontSize: 13,
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
// Avatar picker
// ---------------------------------------------------------------------------

class _AvatarPicker extends StatelessWidget {
  const _AvatarPicker({
    required this.user,
    required this.file,
    required this.onTap,
  });

  final UserModel user;
  final File? file;
  final VoidCallback onTap;

  String _initials(String text) {
    final parts = text.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    if (text.length >= 2) return text.substring(0, 2).toUpperCase();
    return text.toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Stack(
        children: [
          Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: AppColors.skyGradientDiagonal,
            ),
            child: file != null
                ? ClipOval(
                    child: Image.file(
                      file!,
                      fit: BoxFit.cover,
                      width: 96,
                      height: 96,
                    ),
                  )
                : Center(
                    child: Text(
                      _initials(user.name ?? user.phone ?? user.email ?? '?'),
                      style: GoogleFonts.nunito(
                        fontSize: 32,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
          ),
          Positioned(
            bottom: 0,
            right: 0,
            child: Container(
              width: 30,
              height: 30,
              decoration: BoxDecoration(
                color: AppColors.navy,
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white, width: 2),
              ),
              child: const Icon(Icons.camera_alt_rounded,
                  color: Colors.white, size: 15),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

class _SectionLabel extends StatelessWidget {
  const _SectionLabel({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: GoogleFonts.nunito(
        fontSize: 14,
        fontWeight: FontWeight.w800,
        color: AppColors.navy,
      ),
    );
  }
}

class _FormField extends StatelessWidget {
  const _FormField({
    required this.controller,
    required this.label,
    required this.hint,
    this.validator,
    this.keyboardType,
  });

  final TextEditingController controller;
  final String label;
  final String hint;
  final String? Function(String?)? validator;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: validator,
      style: GoogleFonts.nunito(
        fontSize: 14,
        color: AppColors.navy,
      ),
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
      ),
    );
  }
}

class _OperatorPicker extends StatelessWidget {
  const _OperatorPicker({
    required this.selected,
    required this.operators,
    required this.onChanged,
  });

  final String? selected;
  final List<String> operators;
  final void Function(String?) onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: operators.map((op) {
        final isSelected = op == selected;
        return Expanded(
          child: GestureDetector(
            onTap: () => onChanged(isSelected ? null : op),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              margin: EdgeInsets.only(
                right: op == operators.last ? 0 : 8,
              ),
              padding: const EdgeInsets.symmetric(vertical: 12),
              decoration: BoxDecoration(
                gradient: isSelected ? AppColors.skyGradient : null,
                color: isSelected ? null : AppColors.white,
                border: Border.all(
                  color: isSelected
                      ? Colors.transparent
                      : AppColors.border,
                ),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Column(
                children: [
                  Text(
                    op == 'MTN'
                        ? '📱'
                        : op == 'Moov'
                            ? '📲'
                            : '📡',
                    style: const TextStyle(fontSize: 22),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    op,
                    style: GoogleFonts.nunito(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: isSelected ? Colors.white : AppColors.navy,
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _InterestsPicker extends StatelessWidget {
  const _InterestsPicker({
    required this.selected,
    required this.all,
    required this.onChanged,
  });

  final List<String> selected;
  final List<String> all;
  final void Function(List<String>) onChanged;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: all.map((interest) {
        final isSelected = selected.contains(interest);
        return GestureDetector(
          onTap: () {
            final updated = List<String>.from(selected);
            if (isSelected) {
              updated.remove(interest);
            } else {
              updated.add(interest);
            }
            onChanged(updated);
          },
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            padding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(
              gradient: isSelected ? AppColors.skyGradient : null,
              color: isSelected ? null : AppColors.white,
              border: Border.all(
                color: isSelected ? Colors.transparent : AppColors.border,
              ),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              interest,
              style: GoogleFonts.nunito(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isSelected ? Colors.white : AppColors.navy,
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}

// ---------------------------------------------------------------------------
// Section critères d'audience dynamiques pour le profil
// ---------------------------------------------------------------------------

/// Affiche les critères d'audience non natifs requis pour le profil
/// (isBuiltin == false && isRequiredForProfile == true).
///
/// Utilise [audienceCriteriaProvider] directement pour rester découplé
/// du reste de l'écran.
class _ProfileDynamicCriteriaSection extends ConsumerWidget {
  const _ProfileDynamicCriteriaSection({
    required this.customFields,
    required this.onChanged,
  });

  final Map<String, dynamic> customFields;
  final void Function(String name, dynamic value) onChanged;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final criteriaAsync = ref.watch(audienceCriteriaProvider);

    return criteriaAsync.when(
      loading: () => const SizedBox.shrink(),
      error: (e, st) => const SizedBox.shrink(),
      data: (criteria) {
        final requiredCriteria = criteria
            .where((c) => !c.isBuiltin && c.isRequiredForProfile && c.isActive)
            .toList()
          ..sort((a, b) => a.sortOrder.compareTo(b.sortOrder));

        if (requiredCriteria.isEmpty) return const SizedBox.shrink();

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Informations complémentaires',
              style: GoogleFonts.nunito(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Ces informations permettent de personnaliser votre expérience',
              style: GoogleFonts.nunito(
                fontSize: 12,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 10),
            ...requiredCriteria.asMap().entries.map((entry) {
              final idx = entry.key;
              final criterion = entry.value;
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (idx > 0) const SizedBox(height: 12),
                  _ProfileCriterionWidget(
                    criterion: criterion,
                    value: customFields[criterion.name],
                    onChanged: (v) => onChanged(criterion.name, v),
                  ),
                ],
              );
            }),
          ],
        );
      },
    );
  }
}

class _ProfileCriterionWidget extends StatelessWidget {
  const _ProfileCriterionWidget({
    required this.criterion,
    required this.value,
    required this.onChanged,
  });

  final AudienceCriterionConfig criterion;
  final dynamic value;
  final void Function(dynamic) onChanged;

  @override
  Widget build(BuildContext context) {
    return switch (criterion.type) {
      'select' => _ProfileSelectField(
          criterion: criterion,
          value: value as String?,
          onChanged: onChanged,
        ),
      'multiselect' => _ProfileMultiselectField(
          criterion: criterion,
          value: value is List<String> ? value as List<String> : const [],
          onChanged: onChanged,
        ),
      'boolean' => _ProfileBooleanField(
          criterion: criterion,
          value: value as bool? ?? false,
          onChanged: onChanged,
        ),
      _ => _ProfileTextField(
          criterion: criterion,
          value: value?.toString() ?? '',
          onChanged: onChanged,
        ),
    };
  }
}

class _ProfileSelectField extends StatelessWidget {
  const _ProfileSelectField({
    required this.criterion,
    required this.value,
    required this.onChanged,
  });

  final AudienceCriterionConfig criterion;
  final String? value;
  final void Function(dynamic) onChanged;

  @override
  Widget build(BuildContext context) {
    final opts = criterion.options ?? const [];
    return DropdownButtonFormField<String>(
      initialValue: opts.contains(value) ? value : null,
      decoration: InputDecoration(
        labelText: criterion.label,
        hintText: 'Sélectionner…',
        hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
      ),
      style: GoogleFonts.nunito(fontSize: 14, color: AppColors.navy),
      items: opts
          .map((o) => DropdownMenuItem(value: o, child: Text(o)))
          .toList(),
      onChanged: onChanged,
    );
  }
}

class _ProfileMultiselectField extends StatelessWidget {
  const _ProfileMultiselectField({
    required this.criterion,
    required this.value,
    required this.onChanged,
  });

  final AudienceCriterionConfig criterion;
  final List<String> value;
  final void Function(dynamic) onChanged;

  @override
  Widget build(BuildContext context) {
    final opts = criterion.options ?? const [];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          criterion.label,
          style: GoogleFonts.nunito(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: AppColors.navy,
          ),
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: opts.map((opt) {
            final isSelected = value.contains(opt);
            return GestureDetector(
              onTap: () {
                final updated = List<String>.from(value);
                if (isSelected) {
                  updated.remove(opt);
                } else {
                  updated.add(opt);
                }
                onChanged(updated);
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                padding: const EdgeInsets.symmetric(
                    horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  gradient: isSelected ? AppColors.skyGradient : null,
                  color: isSelected ? null : AppColors.white,
                  border: Border.all(
                    color: isSelected
                        ? Colors.transparent
                        : AppColors.border,
                  ),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  opt,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isSelected ? Colors.white : AppColors.navy,
                  ),
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}

class _ProfileBooleanField extends StatelessWidget {
  const _ProfileBooleanField({
    required this.criterion,
    required this.value,
    required this.onChanged,
  });

  final AudienceCriterionConfig criterion;
  final bool value;
  final void Function(dynamic) onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            criterion.label,
            style: GoogleFonts.nunito(
              fontSize: 14,
              color: AppColors.navy,
            ),
          ),
        ),
        Switch(
          value: value,
          activeThumbColor: AppColors.sky,
          onChanged: onChanged,
        ),
      ],
    );
  }
}

class _ProfileTextField extends StatelessWidget {
  const _ProfileTextField({
    required this.criterion,
    required this.value,
    required this.onChanged,
  });

  final AudienceCriterionConfig criterion;
  final String value;
  final void Function(dynamic) onChanged;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      initialValue: value,
      keyboardType: criterion.type == 'number'
          ? TextInputType.number
          : TextInputType.text,
      style: GoogleFonts.nunito(fontSize: 14, color: AppColors.navy),
      decoration: InputDecoration(
        labelText: criterion.label,
        hintText: criterion.label,
        hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
      ),
      onChanged: onChanged,
    );
  }
}
