<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method'        => ['sometimes', 'in:phone,email'],
            'phone'         => ['required_without:email', 'nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'email'         => ['required_without:phone', 'nullable', 'email', 'max:255'],
            'role'          => ['required', 'in:subscriber,advertiser'],
            'name'          => ['required_if:role,advertiser', 'nullable', 'string', 'max:100'],
            'referral_code' => ['nullable', 'string', 'size:8', 'exists:subscriber_profiles,referral_code'],
            // Granular consents sent from the mobile registration form
            'consent_cgu'           => ['nullable', 'boolean'],
            'consent_targeting'     => ['nullable', 'boolean'],
            'consent_transfer'      => ['nullable', 'boolean'],
            'consent_fingerprint'   => ['nullable', 'boolean'],
            'consent_notifications' => ['nullable', 'boolean'],
            'consent_marketing'     => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without'      => 'Le numéro de téléphone ou l\'adresse e-mail est requis.',
            'phone.regex'                 => 'Le format du numéro de téléphone est invalide.',
            'email.required_without'      => 'L\'adresse e-mail ou le numéro de téléphone est requis.',
            'email.email'                 => 'L\'adresse e-mail est invalide.',
            'role.required'               => 'Le rôle est requis.',
            'role.in'                     => 'Le rôle doit être subscriber ou advertiser.',
            'name.required_if'            => 'Le nom est requis pour un compte annonceur.',
            'name.max'                    => 'Le nom ne peut pas dépasser 100 caractères.',
            'referral_code.size'          => 'Le code de parrainage doit comporter exactement 8 caractères.',
            'referral_code.exists'        => 'Le code de parrainage est invalide.',
        ];
    }
}
