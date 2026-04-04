import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/config/platform_config_model.dart';
import '../../../../core/config/platform_config_provider.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/campaign_model.dart';
import '../providers/campaign_provider.dart';

// ---------------------------------------------------------------------------
// Intérêts prédéfinis pour le ciblage
// ---------------------------------------------------------------------------

const _interestOptions = [
  'Mode', 'Tech', 'Sport', 'Musique', 'Cuisine',
  'Voyage', 'Santé', 'Finance', 'Auto', 'Beauté',
  'Éducation', 'Gaming', 'Cinéma', 'Business',
];

// ---------------------------------------------------------------------------
// CampaignFormScreen
// ---------------------------------------------------------------------------

class CampaignFormScreen extends ConsumerStatefulWidget {
  const CampaignFormScreen({
    super.key,
    this.existingCampaign,
  });

  /// Si non nul, on est en mode édition.
  final CampaignModel? existingCampaign;

  @override
  ConsumerState<CampaignFormScreen> createState() =>
      _CampaignFormScreenState();
}

class _CampaignFormScreenState extends ConsumerState<CampaignFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleCtrl = TextEditingController();
  final _descriptionCtrl = TextEditingController();
  final _budgetCtrl = TextEditingController();
  final _costPerViewCtrl = TextEditingController();
  final _durationCtrl = TextEditingController();

  String _selectedFormat = 'video';
  String _selectedEndMode = 'target_reached';
  String _selectedGender = 'all';
  int _ageMin = 18;
  int _ageMax = 45;
  final Set<String> _selectedInterests = {};

  /// Valeurs des critères d'audience dynamiques (non natifs).
  /// Clé = criterion.name, valeur = dynamic (String, List<String>, bool).
  final Map<String, dynamic> _dynamicCriteria = {};

  XFile? _selectedMedia;
  XFile? _selectedThumbnail;

  bool get _isEditMode => widget.existingCampaign != null;

  @override
  void initState() {
    super.initState();
    _initFromExisting();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(campaignFormProvider.notifier).reset();
    });
  }

  void _initFromExisting() {
    final c = widget.existingCampaign;
    if (c == null) return;

    _titleCtrl.text = c.title;
    _descriptionCtrl.text = c.description ?? '';
    _budgetCtrl.text = c.budget.toString();
    _costPerViewCtrl.text = c.costPerView.toString();
    if (c.durationSeconds != null) {
      _durationCtrl.text = c.durationSeconds.toString();
    }
    _selectedFormat = c.format.name;
    if (c.endMode != null) _selectedEndMode = c.endMode!;

    final t = c.targeting;
    if (t != null) {
      _ageMin = (t['age_min'] as num?)?.toInt() ?? 18;
      _ageMax = (t['age_max'] as num?)?.toInt() ?? 45;
      _selectedGender = t['gender'] as String? ?? 'all';
      final interests = t['interests'];
      if (interests is List) {
        _selectedInterests.addAll(interests.map((e) => e.toString()));
      }
      // Recharger les critères dynamiques sauvegardés
      final savedDynamic = t['dynamic_criteria'];
      if (savedDynamic is Map<String, dynamic>) {
        _dynamicCriteria.addAll(savedDynamic);
      }
    }
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descriptionCtrl.dispose();
    _budgetCtrl.dispose();
    _costPerViewCtrl.dispose();
    _durationCtrl.dispose();
    super.dispose();
  }

  Map<String, dynamic> get _targeting => {
        'age_min': _ageMin,
        'age_max': _ageMax,
        'gender': _selectedGender,
        if (_selectedInterests.isNotEmpty)
          'interests': _selectedInterests.toList(),
        if (_dynamicCriteria.isNotEmpty)
          'dynamic_criteria': Map<String, dynamic>.from(_dynamicCriteria),
      };

  Future<void> _pickMedia() async {
    final picker = ImagePicker();
    final file = await picker.pickMedia();
    if (file != null) setState(() => _selectedMedia = file);
  }

  Future<void> _pickThumbnail() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.gallery);
    if (file != null) setState(() => _selectedThumbnail = file);
  }

  Future<void> _save({required bool submitAfterSave}) async {
    if (!_formKey.currentState!.validate()) return;

    final notifier = ref.read(campaignFormProvider.notifier);

    final saved = await notifier.saveDraft(
      title: _titleCtrl.text.trim(),
      description: _descriptionCtrl.text.trim().isEmpty
          ? null
          : _descriptionCtrl.text.trim(),
      format: _selectedFormat,
      budget: int.parse(_budgetCtrl.text.trim()),
      costPerView: int.parse(_costPerViewCtrl.text.trim()),
      targeting: _targeting,
      durationSeconds: _durationCtrl.text.trim().isEmpty
          ? null
          : int.tryParse(_durationCtrl.text.trim()),
      existingId: widget.existingCampaign?.id,
      endMode: _selectedEndMode,
    );

    if (!mounted || saved == null) return;

    if (_selectedMedia != null) {
      await notifier.uploadMedia(
        saved.id,
        mediaFilePath: _selectedMedia!.path,
        thumbnailFilePath: _selectedThumbnail?.path,
      );
    }

    if (!mounted) return;
    final formState = ref.read(campaignFormProvider);
    if (formState.errorMessage != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(formState.errorMessage!, style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    if (submitAfterSave) {
      try {
        await ref
            .read(campaignDetailProvider(saved.id).notifier)
            .submit();
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Campagne soumise pour révision !',
                style: GoogleFonts.nunito()),
            backgroundColor: AppColors.success,
            behavior: SnackBarBehavior.floating,
          ),
        );
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString(), style: GoogleFonts.nunito()),
            backgroundColor: AppColors.danger,
            behavior: SnackBarBehavior.floating,
          ),
        );
        return;
      }
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Brouillon enregistré', style: GoogleFonts.nunito()),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }

    if (!mounted) return;
    context.pop();
  }

  // ---- helpers ----

  /// Détermine si le champ durée doit être affiché pour le format sélectionné.
  bool _showDurationField(List<CampaignFormatConfig>? formats) {
    if (formats == null) return _selectedFormat == 'video';
    final fmt = formats.where((f) => f.slug == _selectedFormat).firstOrNull;
    if (fmt == null) return false;
    // Afficher si le format accepte des vidéos ou s'il a une durée par défaut.
    return fmt.acceptedMedia.any((m) => m.startsWith('video')) ||
        fmt.defaultDuration != null;
  }

  @override
  Widget build(BuildContext context) {
    final formState = ref.watch(campaignFormProvider);
    final isBusy = formState.isSaving || formState.isUploading;

    final formatsAsync = ref.watch(campaignFormatsProvider);
    final criteriaAsync = ref.watch(audienceCriteriaProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _FormTopBar(isEditMode: _isEditMode),

          Expanded(
            child: Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                children: [
                  // ---- Informations générales ----
                  _SectionTitle('Informations générales'),
                  const SizedBox(height: 10),

                  _FieldLabel('Titre de la campagne *'),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _titleCtrl,
                    textCapitalization: TextCapitalization.sentences,
                    maxLength: 100,
                    style: GoogleFonts.nunito(
                        color: AppColors.navy, fontWeight: FontWeight.w600),
                    decoration: InputDecoration(
                      hintText: 'Ex : Promotion Ramadan 2025',
                      hintStyle:
                          GoogleFonts.nunito(color: AppColors.textHint),
                      counterText: '',
                    ),
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) {
                        return 'Le titre est obligatoire';
                      }
                      if (v.trim().length < 3) return 'Minimum 3 caractères';
                      return null;
                    },
                  ),

                  const SizedBox(height: 14),

                  _FieldLabel('Description (optionnelle)'),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _descriptionCtrl,
                    maxLines: 3,
                    maxLength: 500,
                    textCapitalization: TextCapitalization.sentences,
                    style: GoogleFonts.nunito(
                        color: AppColors.navy, fontSize: 13),
                    decoration: InputDecoration(
                      hintText: 'Décrivez votre campagne...',
                      hintStyle:
                          GoogleFonts.nunito(color: AppColors.textHint),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // ---- Format publicitaire ----
                  _SectionTitle('Format publicitaire'),
                  const SizedBox(height: 10),

                  formatsAsync.when(
                    loading: () => const _FormatsLoadingPlaceholder(),
                    error: (e, st) => _FormatSelector(
                      selected: _selectedFormat,
                      formats: const [],
                      onChanged: (v) => setState(() => _selectedFormat = v),
                    ),
                    data: (formats) {
                      // Si le format actuel n'existe plus dans la liste API,
                      // on bascule vers le premier format disponible.
                      final slugs = formats.map((f) => f.slug).toList();
                      if (slugs.isNotEmpty &&
                          !slugs.contains(_selectedFormat)) {
                        WidgetsBinding.instance.addPostFrameCallback((_) {
                          if (mounted) {
                            setState(() => _selectedFormat = slugs.first);
                          }
                        });
                      }
                      return _FormatSelector(
                        selected: _selectedFormat,
                        formats: formats,
                        onChanged: (v) => setState(() => _selectedFormat = v),
                      );
                    },
                  ),

                  const SizedBox(height: 20),

                  // ---- Budget ----
                  _SectionTitle('Budget'),
                  const SizedBox(height: 10),

                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _FieldLabel('Budget total *'),
                            const SizedBox(height: 6),
                            TextFormField(
                              controller: _budgetCtrl,
                              keyboardType: TextInputType.number,
                              inputFormatters: [
                                FilteringTextInputFormatter.digitsOnly
                              ],
                              style: GoogleFonts.nunito(
                                  color: AppColors.navy,
                                  fontWeight: FontWeight.w700),
                              decoration: InputDecoration(
                                hintText: '50 000',
                                hintStyle: GoogleFonts.nunito(
                                    color: AppColors.textHint),
                                suffixText: 'FCFA',
                                suffixStyle: GoogleFonts.nunito(
                                    color: AppColors.muted,
                                    fontWeight: FontWeight.w600),
                              ),
                              validator: (v) {
                                if (v == null || v.isEmpty) return 'Requis';
                                final n = int.tryParse(v);
                                if (n == null || n < 1000) {
                                  return 'Min. 1 000 FCFA';
                                }
                                return null;
                              },
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _FieldLabel('Coût par vue *'),
                            const SizedBox(height: 6),
                            TextFormField(
                              controller: _costPerViewCtrl,
                              keyboardType: TextInputType.number,
                              inputFormatters: [
                                FilteringTextInputFormatter.digitsOnly
                              ],
                              style: GoogleFonts.nunito(
                                  color: AppColors.navy,
                                  fontWeight: FontWeight.w700),
                              decoration: InputDecoration(
                                hintText: '5',
                                hintStyle: GoogleFonts.nunito(
                                    color: AppColors.textHint),
                                suffixText: 'FCFA',
                                suffixStyle: GoogleFonts.nunito(
                                    color: AppColors.muted,
                                    fontWeight: FontWeight.w600),
                              ),
                              validator: (v) {
                                if (v == null || v.isEmpty) return 'Requis';
                                final n = int.tryParse(v);
                                if (n == null || n < 1) return 'Min. 1 FCFA';
                                return null;
                              },
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  // Estimation des vues
                  _BudgetEstimate(
                    budgetText: _budgetCtrl.text,
                    costText: _costPerViewCtrl.text,
                  ),

                  const SizedBox(height: 20),

                  // ---- Mode de fin ----
                  _SectionTitle('Condition de fin'),
                  const SizedBox(height: 10),

                  _EndModeSelector(
                    selected: _selectedEndMode,
                    onChanged: (v) => setState(() => _selectedEndMode = v),
                  ),

                  const SizedBox(height: 14),

                  // Durée — affichée selon le format
                  if (_showDurationField(formatsAsync.valueOrNull)) ...[
                    _FieldLabel('Durée de la vidéo (secondes)'),
                    const SizedBox(height: 6),
                    TextFormField(
                      controller: _durationCtrl,
                      keyboardType: TextInputType.number,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                        LengthLimitingTextInputFormatter(3),
                      ],
                      style: GoogleFonts.nunito(
                          color: AppColors.navy, fontWeight: FontWeight.w600),
                      decoration: InputDecoration(
                        hintText: formatsAsync.valueOrNull
                                ?.where((f) => f.slug == _selectedFormat)
                                .firstOrNull
                                ?.defaultDuration
                                ?.toString() ??
                            '30',
                        hintStyle:
                            GoogleFonts.nunito(color: AppColors.textHint),
                        suffixText: 'sec',
                        suffixStyle:
                            GoogleFonts.nunito(color: AppColors.muted),
                      ),
                      validator: (v) {
                        if (v == null || v.isEmpty) return null;
                        final n = int.tryParse(v);
                        if (n == null || n < 5 || n > 120) {
                          return 'Entre 5 et 120 secondes';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 20),
                  ],

                  // ---- Ciblage ----
                  _SectionTitle('Ciblage de l\'audience'),
                  const SizedBox(height: 12),

                  _TargetingSection(
                    ageMin: _ageMin,
                    ageMax: _ageMax,
                    gender: _selectedGender,
                    selectedInterests: _selectedInterests,
                    onAgeMinChanged: (v) => setState(() => _ageMin = v),
                    onAgeMaxChanged: (v) => setState(() => _ageMax = v),
                    onGenderChanged: (v) =>
                        setState(() => _selectedGender = v),
                    onInterestToggled: (interest) {
                      setState(() {
                        if (_selectedInterests.contains(interest)) {
                          _selectedInterests.remove(interest);
                        } else {
                          _selectedInterests.add(interest);
                        }
                      });
                    },
                  ),

                  // Critères d'audience dynamiques (non natifs)
                  criteriaAsync.when(
                    loading: () => const SizedBox.shrink(),
                    error: (e, st) => const SizedBox.shrink(),
                    data: (criteria) {
                      final custom = criteria
                          .where((c) => !c.isBuiltin && c.isActive)
                          .toList()
                        ..sort((a, b) =>
                            a.sortOrder.compareTo(b.sortOrder));
                      if (custom.isEmpty) return const SizedBox.shrink();
                      return Padding(
                        padding: const EdgeInsets.only(top: 12),
                        child: _DynamicCriteriaSection(
                          criteria: custom,
                          values: _dynamicCriteria,
                          onChanged: (name, value) {
                            setState(() => _dynamicCriteria[name] = value);
                          },
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 20),

                  // ---- Médias ----
                  _SectionTitle('Médias'),
                  const SizedBox(height: 10),

                  _MediaUploadSection(
                    selectedMedia: _selectedMedia,
                    selectedThumbnail: _selectedThumbnail,
                    existingMediaUrl: widget.existingCampaign?.mediaUrl,
                    existingThumbnailUrl:
                        widget.existingCampaign?.thumbnailUrl,
                    isUploading: formState.isUploading,
                    onPickMedia: _pickMedia,
                    onPickThumbnail: _pickThumbnail,
                  ),

                  const SizedBox(height: 28),

                  // ---- Boutons ----
                  SkyGradientButton(
                    label: 'Enregistrer en brouillon',
                    onPressed:
                        isBusy ? null : () => _save(submitAfterSave: false),
                    isLoading: isBusy,
                  ),

                  const SizedBox(height: 10),

                  OutlinedButton(
                    onPressed:
                        isBusy ? null : () => _save(submitAfterSave: true),
                    child: isBusy
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: AppColors.sky),
                          )
                        : const Text('Enregistrer et soumettre'),
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
// En-tête du formulaire
// ---------------------------------------------------------------------------

class _FormTopBar extends StatelessWidget {
  const _FormTopBar({required this.isEditMode});
  final bool isEditMode;

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
            isEditMode ? 'Modifier la campagne' : 'Nouvelle campagne',
            style: GoogleFonts.nunito(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Sélecteur de mode de fin (radio cards)
// ---------------------------------------------------------------------------

class _EndModeOption {
  const _EndModeOption({
    required this.value,
    required this.label,
    required this.description,
    required this.icon,
  });

  final String value;
  final String label;
  final String description;
  final IconData icon;
}

const _endModeOptions = [
  _EndModeOption(
    value: 'target_reached',
    label: 'Ciblage atteint',
    description: 'Fin quand le nombre de vues cibles est atteint',
    icon: Icons.track_changes_rounded,
  ),
  _EndModeOption(
    value: 'date',
    label: 'Date précise',
    description: 'Fin à une date et heure définies',
    icon: Icons.event_rounded,
  ),
  _EndModeOption(
    value: 'manual',
    label: 'Arrêt manuel',
    description: 'Vous arrêtez la campagne manuellement',
    icon: Icons.pause_circle_outline_rounded,
  ),
];

class _EndModeSelector extends StatelessWidget {
  const _EndModeSelector({
    required this.selected,
    required this.onChanged,
  });

  final String selected;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: _endModeOptions.map((option) {
        final isSelected = selected == option.value;
        return GestureDetector(
          onTap: () => onChanged(option.value),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: isSelected ? AppColors.skyPale : Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isSelected ? AppColors.sky : AppColors.border,
                width: isSelected ? 1.5 : 1.0,
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: isSelected
                        ? AppColors.sky.withAlpha(25)
                        : AppColors.border.withAlpha(80),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(
                    option.icon,
                    size: 18,
                    color: isSelected ? AppColors.sky : AppColors.muted,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        option.label,
                        style: GoogleFonts.nunito(
                          fontSize: 13,
                          fontWeight: FontWeight.w800,
                          color: isSelected ? AppColors.sky : AppColors.navy,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        option.description,
                        style: GoogleFonts.nunito(
                          fontSize: 11,
                          color: AppColors.muted,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  width: 18,
                  height: 18,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: isSelected ? AppColors.sky : AppColors.border,
                      width: isSelected ? 5 : 2,
                    ),
                    color: isSelected ? AppColors.sky : Colors.white,
                  ),
                  child: isSelected
                      ? const Icon(Icons.check, size: 10, color: Colors.white)
                      : null,
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }
}

// ---------------------------------------------------------------------------
// Placeholder de chargement des formats
// ---------------------------------------------------------------------------

class _FormatsLoadingPlaceholder extends StatelessWidget {
  const _FormatsLoadingPlaceholder();

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 10,
      mainAxisSpacing: 10,
      childAspectRatio: 2.2,
      children: List.generate(
        4,
        (_) => Container(
          decoration: BoxDecoration(
            color: AppColors.border.withAlpha(100),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Center(
            child: SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(
                  strokeWidth: 2, color: AppColors.sky),
            ),
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Sélecteur de format (dynamique)
// ---------------------------------------------------------------------------

class _FormatSelector extends StatelessWidget {
  const _FormatSelector({
    required this.selected,
    required this.formats,
    required this.onChanged,
  });

  final String selected;
  final List<CampaignFormatConfig> formats;
  final void Function(String) onChanged;

  /// Icône Material par défaut selon le slug.
  IconData _iconFor(CampaignFormatConfig fmt) {
    return switch (fmt.slug) {
      'video' => Icons.play_circle_outline_rounded,
      'flash' => Icons.bolt_rounded,
      'quiz' => Icons.quiz_rounded,
      'scratch' => Icons.auto_awesome_rounded,
      _ => Icons.campaign_rounded,
    };
  }

  @override
  Widget build(BuildContext context) {
    if (formats.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.skyPale,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.border),
        ),
        child: Text(
          'Aucun format disponible pour le moment.',
          style: GoogleFonts.nunito(fontSize: 13, color: AppColors.muted),
        ),
      );
    }

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 10,
      mainAxisSpacing: 10,
      childAspectRatio: 2.2,
      children: formats.map((fmt) {
        final isSelected = selected == fmt.slug;
        return GestureDetector(
          onTap: () => onChanged(fmt.slug),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            decoration: BoxDecoration(
              color: isSelected ? AppColors.skyPale : Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isSelected ? AppColors.sky : AppColors.border,
                width: isSelected ? 2 : 1.5,
              ),
            ),
            child: Row(
              children: [
                const SizedBox(width: 12),
                Icon(
                  _iconFor(fmt),
                  size: 22,
                  color: isSelected ? AppColors.sky : AppColors.muted,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        fmt.label,
                        style: GoogleFonts.nunito(
                          fontSize: 13,
                          fontWeight: FontWeight.w800,
                          color:
                              isSelected ? AppColors.navy : AppColors.muted,
                        ),
                      ),
                      if (fmt.multiplier != 1.0)
                        Text(
                          'x${fmt.multiplier.toStringAsFixed(1)}',
                          style: GoogleFonts.nunito(
                            fontSize: 10,
                            color: isSelected
                                ? AppColors.sky2
                                : AppColors.textHint,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }
}

// ---------------------------------------------------------------------------
// Estimation du budget
// ---------------------------------------------------------------------------

class _BudgetEstimate extends StatelessWidget {
  const _BudgetEstimate({
    required this.budgetText,
    required this.costText,
  });

  final String budgetText;
  final String costText;

  @override
  Widget build(BuildContext context) {
    final budget = int.tryParse(budgetText);
    final cost = int.tryParse(costText);

    if (budget == null || cost == null || cost == 0) {
      return const SizedBox.shrink();
    }
    final estimatedViews = budget ~/ cost;

    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: Container(
        padding:
            const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: AppColors.skyPale,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            const Icon(Icons.info_outline_rounded,
                size: 16, color: AppColors.sky),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'Estimation : environ '
                '${Formatters.compact(estimatedViews)} vues pour ce budget',
                style: GoogleFonts.nunito(
                  fontSize: 12,
                  color: AppColors.sky2,
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
// Section ciblage (hardcodée : âge, genre, intérêts)
// ---------------------------------------------------------------------------

class _TargetingSection extends StatelessWidget {
  const _TargetingSection({
    required this.ageMin,
    required this.ageMax,
    required this.gender,
    required this.selectedInterests,
    required this.onAgeMinChanged,
    required this.onAgeMaxChanged,
    required this.onGenderChanged,
    required this.onInterestToggled,
  });

  final int ageMin;
  final int ageMax;
  final String gender;
  final Set<String> selectedInterests;
  final void Function(int) onAgeMinChanged;
  final void Function(int) onAgeMaxChanged;
  final void Function(String) onGenderChanged;
  final void Function(String) onInterestToggled;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Tranche d'âge
          _FieldLabel('Tranche d\'âge'),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: Column(
                  children: [
                    Text(
                      'Min : $ageMin ans',
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        color: AppColors.navy,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    Slider(
                      value: ageMin.toDouble(),
                      min: 13,
                      max: ageMax.toDouble() - 1,
                      divisions: (ageMax - 14).clamp(1, 100),
                      activeColor: AppColors.sky,
                      inactiveColor: AppColors.border,
                      onChanged: (v) => onAgeMinChanged(v.round()),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  children: [
                    Text(
                      'Max : $ageMax ans',
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        color: AppColors.navy,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    Slider(
                      value: ageMax.toDouble(),
                      min: ageMin.toDouble() + 1,
                      max: 65,
                      divisions: (65 - ageMin).clamp(1, 100),
                      activeColor: AppColors.sky,
                      inactiveColor: AppColors.border,
                      onChanged: (v) => onAgeMaxChanged(v.round()),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),
          const Divider(color: AppColors.border, height: 1),
          const SizedBox(height: 12),

          // Genre
          _FieldLabel('Genre cible'),
          const SizedBox(height: 8),
          Row(
            children: [
              _GenderChip(
                label: 'Tous',
                value: 'all',
                selected: gender == 'all',
                onTap: () => onGenderChanged('all'),
              ),
              const SizedBox(width: 8),
              _GenderChip(
                label: 'Hommes',
                value: 'male',
                selected: gender == 'male',
                onTap: () => onGenderChanged('male'),
              ),
              const SizedBox(width: 8),
              _GenderChip(
                label: 'Femmes',
                value: 'female',
                selected: gender == 'female',
                onTap: () => onGenderChanged('female'),
              ),
            ],
          ),

          const SizedBox(height: 12),
          const Divider(color: AppColors.border, height: 1),
          const SizedBox(height: 12),

          // Centres d'intérêt
          _FieldLabel('Centres d\'intérêt'),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _interestOptions.map((interest) {
              final selected = selectedInterests.contains(interest);
              return GestureDetector(
                onTap: () => onInterestToggled(interest),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  padding: const EdgeInsets.symmetric(
                      horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: selected ? AppColors.skyPale : Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color:
                          selected ? AppColors.sky : AppColors.border,
                      width: selected ? 1.5 : 1,
                    ),
                  ),
                  child: Text(
                    interest,
                    style: GoogleFonts.nunito(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: selected ? AppColors.sky2 : AppColors.muted,
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}

class _GenderChip extends StatelessWidget {
  const _GenderChip({
    required this.label,
    required this.value,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final String value;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          gradient: selected ? AppColors.skyGradient : null,
          color: selected ? null : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color:
                selected ? Colors.transparent : AppColors.border,
          ),
        ),
        child: Text(
          label,
          style: GoogleFonts.nunito(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: selected ? Colors.white : AppColors.muted,
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section critères dynamiques (ciblage non natif)
// ---------------------------------------------------------------------------

class _DynamicCriteriaSection extends StatelessWidget {
  const _DynamicCriteriaSection({
    required this.criteria,
    required this.values,
    required this.onChanged,
  });

  final List<AudienceCriterionConfig> criteria;
  final Map<String, dynamic> values;
  final void Function(String name, dynamic value) onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _FieldLabel('Critères supplémentaires'),
          const SizedBox(height: 12),
          ...criteria.asMap().entries.map((entry) {
            final idx = entry.key;
            final criterion = entry.value;
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (idx > 0) ...[
                  const Divider(color: AppColors.border, height: 1),
                  const SizedBox(height: 12),
                ],
                _DynamicCriterionWidget(
                  criterion: criterion,
                  value: values[criterion.name],
                  onChanged: (v) => onChanged(criterion.name, v),
                ),
                const SizedBox(height: 12),
              ],
            );
          }),
        ],
      ),
    );
  }
}

class _DynamicCriterionWidget extends StatelessWidget {
  const _DynamicCriterionWidget({
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
      'select' => _SelectCriterion(
          criterion: criterion,
          value: value as String?,
          onChanged: onChanged,
        ),
      'multiselect' => _MultiselectCriterion(
          criterion: criterion,
          value: value is List<String>
              ? value as List<String>
              : const [],
          onChanged: onChanged,
        ),
      'boolean' => _BooleanCriterion(
          criterion: criterion,
          value: value as bool? ?? false,
          onChanged: onChanged,
        ),
      _ => _TextCriterion(
          criterion: criterion,
          value: value?.toString() ?? '',
          onChanged: onChanged,
        ),
    };
  }
}

class _SelectCriterion extends StatelessWidget {
  const _SelectCriterion({
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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _FieldLabel(criterion.label),
        const SizedBox(height: 6),
        DropdownButtonFormField<String>(
          initialValue: opts.contains(value) ? value : null,
          decoration: InputDecoration(
            hintText: 'Sélectionner…',
            hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
          ),
          style: GoogleFonts.nunito(
              fontSize: 13, color: AppColors.navy),
          items: opts
              .map((o) => DropdownMenuItem(value: o, child: Text(o)))
              .toList(),
          onChanged: onChanged,
        ),
      ],
    );
  }
}

class _MultiselectCriterion extends StatelessWidget {
  const _MultiselectCriterion({
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
        _FieldLabel(criterion.label),
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
                duration: const Duration(milliseconds: 150),
                padding: const EdgeInsets.symmetric(
                    horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color:
                      isSelected ? AppColors.skyPale : Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: isSelected
                        ? AppColors.sky
                        : AppColors.border,
                    width: isSelected ? 1.5 : 1,
                  ),
                ),
                child: Text(
                  opt,
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: isSelected
                        ? AppColors.sky2
                        : AppColors.muted,
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

class _BooleanCriterion extends StatelessWidget {
  const _BooleanCriterion({
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
              fontSize: 13,
              fontWeight: FontWeight.w700,
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

class _TextCriterion extends StatelessWidget {
  const _TextCriterion({
    required this.criterion,
    required this.value,
    required this.onChanged,
  });

  final AudienceCriterionConfig criterion;
  final String value;
  final void Function(dynamic) onChanged;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _FieldLabel(criterion.label),
        const SizedBox(height: 6),
        TextFormField(
          initialValue: value,
          keyboardType: criterion.type == 'number'
              ? TextInputType.number
              : TextInputType.text,
          style: GoogleFonts.nunito(
              fontSize: 13, color: AppColors.navy),
          decoration: InputDecoration(
            hintText: criterion.label,
            hintStyle:
                GoogleFonts.nunito(color: AppColors.textHint),
          ),
          onChanged: onChanged,
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Section upload de médias
// ---------------------------------------------------------------------------

class _MediaUploadSection extends StatelessWidget {
  const _MediaUploadSection({
    required this.selectedMedia,
    required this.selectedThumbnail,
    required this.existingMediaUrl,
    required this.existingThumbnailUrl,
    required this.isUploading,
    required this.onPickMedia,
    required this.onPickThumbnail,
  });

  final XFile? selectedMedia;
  final XFile? selectedThumbnail;
  final String? existingMediaUrl;
  final String? existingThumbnailUrl;
  final bool isUploading;
  final VoidCallback onPickMedia;
  final VoidCallback onPickThumbnail;

  @override
  Widget build(BuildContext context) {
    final hasMedia =
        selectedMedia != null || (existingMediaUrl?.isNotEmpty == true);
    final hasThumbnail = selectedThumbnail != null ||
        (existingThumbnailUrl?.isNotEmpty == true);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Upload principal
        GestureDetector(
          onTap: isUploading ? null : onPickMedia,
          child: Container(
            height: 120,
            width: double.infinity,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: hasMedia ? AppColors.sky : AppColors.border,
                width: hasMedia ? 2 : 1.5,
              ),
            ),
            child: isUploading
                ? const Center(
                    child: CircularProgressIndicator(
                        color: AppColors.sky),
                  )
                : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        hasMedia
                            ? Icons.check_circle_rounded
                            : Icons.upload_file_rounded,
                        size: 32,
                        color: hasMedia
                            ? AppColors.success
                            : AppColors.muted,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        hasMedia
                            ? (selectedMedia?.name ??
                                'Fichier existant - cliquez pour changer')
                            : 'Sélectionner le fichier principal\n'
                                '(vidéo, image ou GIF)',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: hasMedia
                              ? AppColors.success
                              : AppColors.muted,
                          fontWeight: hasMedia
                              ? FontWeight.w700
                              : FontWeight.w400,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
          ),
        ),

        const SizedBox(height: 10),

        // Vignette optionnelle
        GestureDetector(
          onTap: isUploading ? null : onPickThumbnail,
          child: Container(
            height: 60,
            width: double.infinity,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.border),
            ),
            child: Row(
              children: [
                const SizedBox(width: 14),
                Icon(
                  hasThumbnail
                      ? Icons.image_rounded
                      : Icons.add_photo_alternate_outlined,
                  size: 22,
                  color: hasThumbnail ? AppColors.sky : AppColors.muted,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    hasThumbnail
                        ? (selectedThumbnail?.name ??
                            'Vignette existante - changer')
                        : 'Ajouter une vignette (optionnel)',
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      color: hasThumbnail
                          ? AppColors.navy
                          : AppColors.muted,
                      fontWeight: hasThumbnail
                          ? FontWeight.w600
                          : FontWeight.w400,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (hasThumbnail)
                  const Padding(
                    padding: EdgeInsets.only(right: 12),
                    child: Icon(Icons.check_circle_rounded,
                        size: 18, color: AppColors.success),
                  ),
                const SizedBox(width: 14),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Composants partagés
// ---------------------------------------------------------------------------

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.title);
  final String title;

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: GoogleFonts.nunito(
        fontSize: 15,
        fontWeight: FontWeight.w800,
        color: AppColors.navy,
      ),
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
        fontSize: 13,
        color: AppColors.navy,
      ),
    );
  }
}
