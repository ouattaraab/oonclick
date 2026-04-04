import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../data/models/offer_model.dart';
import '../providers/offer_provider.dart';

/// Ecran de soumission d'une demande de cashback pour une offre partenaire.
class ClaimScreen extends ConsumerStatefulWidget {
  const ClaimScreen({super.key, required this.offer});

  final OfferModel offer;

  @override
  ConsumerState<ClaimScreen> createState() => _ClaimScreenState();
}

class _ClaimScreenState extends ConsumerState<ClaimScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _refController = TextEditingController();

  bool _isSubmitting = false;

  @override
  void dispose() {
    _amountController.dispose();
    _refController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);

    try {
      final amount = int.parse(_amountController.text.trim());
      final result = await ref.read(offersProvider.notifier).claimOffer(
            offerId: widget.offer.id,
            purchaseAmount: amount,
            receiptReference: _refController.text.trim().isNotEmpty
                ? _refController.text.trim()
                : null,
          );

      if (mounted) {
        _showResultDialog(result);
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
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showResultDialog(ClaimResult result) {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _ClaimResultDialog(
        result: result,
        onDone: () {
          Navigator.of(context).pop();
          context.pop();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final offer = widget.offer;

    // Calcul cashback estimé en direct
    final rawAmount = int.tryParse(_amountController.text) ?? 0;
    final estimatedCashback =
        (rawAmount * offer.cashbackPercent / 100).floor();

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // Top bar
          Container(
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
                    offer.partnerName,
                    style: GoogleFonts.nunito(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
          ),

          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Offer summary card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: AppColors.skyGradientDiagonal,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 56,
                            height: 56,
                            decoration: BoxDecoration(
                              color: Colors.white.withAlpha(30),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: offer.logoUrl != null
                                ? ClipRRect(
                                    borderRadius: BorderRadius.circular(12),
                                    child: Image.network(
                                      offer.logoUrl!,
                                      fit: BoxFit.contain,
                                      errorBuilder: (_, __, ___) =>
                                          Center(
                                            child: Text(
                                              offer.partnerName[0]
                                                  .toUpperCase(),
                                              style: GoogleFonts.nunito(
                                                fontSize: 22,
                                                fontWeight: FontWeight.w900,
                                                color: Colors.white,
                                              ),
                                            ),
                                          ),
                                    ),
                                  )
                                : Center(
                                    child: Text(
                                      offer.partnerName[0].toUpperCase(),
                                      style: GoogleFonts.nunito(
                                        fontSize: 22,
                                        fontWeight: FontWeight.w900,
                                        color: Colors.white,
                                      ),
                                    ),
                                  ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  offer.partnerName,
                                  style: GoogleFonts.nunito(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w800,
                                    color: Colors.white,
                                  ),
                                ),
                                Text(
                                  '${offer.cashbackPercent.toStringAsFixed(offer.cashbackPercent == offer.cashbackPercent.roundToDouble() ? 0 : 1)}% de cashback sur vos achats',
                                  style: GoogleFonts.nunito(
                                    fontSize: 12,
                                    color: Colors.white.withAlpha(200),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 24),

                    Text(
                      'Renseigner votre achat',
                      style: GoogleFonts.nunito(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.navy,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Entrez le montant de votre achat chez ${offer.partnerName} pour calculer votre cashback.',
                      style: GoogleFonts.nunito(
                          fontSize: 12, color: AppColors.muted),
                    ),

                    const SizedBox(height: 20),

                    // Purchase amount field
                    Text(
                      'Montant d\'achat (FCFA) *',
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.navy,
                      ),
                    ),
                    const SizedBox(height: 6),
                    TextFormField(
                      controller: _amountController,
                      keyboardType: TextInputType.number,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                      ],
                      onChanged: (_) => setState(() {}),
                      decoration: InputDecoration(
                        hintText: 'Ex: 25000',
                        hintStyle: GoogleFonts.nunito(
                            fontSize: 13, color: AppColors.muted),
                        prefixIcon: const Icon(
                          Icons.shopping_bag_outlined,
                          color: AppColors.sky,
                          size: 20,
                        ),
                        suffixText: 'FCFA',
                        suffixStyle: GoogleFonts.nunito(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppColors.muted,
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: AppColors.border),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: AppColors.border),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide:
                              const BorderSide(color: AppColors.sky, width: 1.5),
                        ),
                        filled: true,
                        fillColor: AppColors.white,
                      ),
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) {
                          return 'Veuillez saisir le montant d\'achat';
                        }
                        final n = int.tryParse(v.trim());
                        if (n == null || n < 1) {
                          return 'Montant invalide (minimum 1 FCFA)';
                        }
                        return null;
                      },
                    ),

                    // Estimated cashback display
                    if (rawAmount > 0) ...[
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 14, vertical: 10),
                        decoration: BoxDecoration(
                          color: AppColors.successLight,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.account_balance_wallet_rounded,
                                color: AppColors.success, size: 18),
                            const SizedBox(width: 8),
                            Text(
                              'Cashback estimé : ',
                              style: GoogleFonts.nunito(
                                fontSize: 13,
                                color: AppColors.success,
                              ),
                            ),
                            Text(
                              Formatters.currency(estimatedCashback),
                              style: GoogleFonts.nunito(
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                                color: AppColors.success,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    const SizedBox(height: 20),

                    // Receipt reference field
                    Text(
                      'Référence de reçu (optionnel)',
                      style: GoogleFonts.nunito(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.navy,
                      ),
                    ),
                    const SizedBox(height: 6),
                    TextFormField(
                      controller: _refController,
                      decoration: InputDecoration(
                        hintText: 'N° de transaction, N° reçu…',
                        hintStyle: GoogleFonts.nunito(
                            fontSize: 13, color: AppColors.muted),
                        prefixIcon: const Icon(
                          Icons.receipt_long_outlined,
                          color: AppColors.sky,
                          size: 20,
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: AppColors.border),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: AppColors.border),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide:
                              const BorderSide(color: AppColors.sky, width: 1.5),
                        ),
                        filled: true,
                        fillColor: AppColors.white,
                      ),
                    ),

                    const SizedBox(height: 32),

                    SkyGradientButton(
                      label: _isSubmitting
                          ? 'Envoi en cours…'
                          : 'Soumettre ma demande',
                      onPressed: _isSubmitting ? null : _submit,
                      height: 52,
                    ),

                    const SizedBox(height: 16),
                    Center(
                      child: Text(
                        'Votre demande sera examinée sous 48h.',
                        style: GoogleFonts.nunito(
                            fontSize: 11, color: AppColors.muted),
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
// Result dialog
// ---------------------------------------------------------------------------

class _ClaimResultDialog extends StatelessWidget {
  const _ClaimResultDialog({
    required this.result,
    required this.onDone,
  });

  final ClaimResult result;
  final VoidCallback onDone;

  @override
  Widget build(BuildContext context) {
    final isCredited = result.isCredited;

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isCredited ? AppColors.successLight : AppColors.warnLight,
              ),
              child: Icon(
                isCredited
                    ? Icons.check_circle_rounded
                    : Icons.hourglass_top_rounded,
                color: isCredited ? AppColors.success : AppColors.warn,
                size: 40,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              isCredited ? 'Cashback crédité !' : 'Demande envoyée',
              style: GoogleFonts.nunito(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              result.message,
              textAlign: TextAlign.center,
              style: GoogleFonts.nunito(fontSize: 13, color: AppColors.muted),
            ),
            if (result.cashbackAmount > 0) ...[
              const SizedBox(height: 16),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                decoration: BoxDecoration(
                  color: isCredited
                      ? AppColors.successLight
                      : AppColors.warnLight,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.account_balance_wallet_rounded,
                      color:
                          isCredited ? AppColors.success : AppColors.warn,
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    Text(
                      Formatters.currency(result.cashbackAmount),
                      style: GoogleFonts.nunito(
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                        color: isCredited ? AppColors.success : AppColors.warn,
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 24),
            SkyGradientButton(
              label: 'Fermer',
              onPressed: onDone,
              height: 46,
            ),
          ],
        ),
      ),
    );
  }
}
