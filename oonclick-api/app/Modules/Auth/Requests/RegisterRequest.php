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
            'phone'         => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'role'          => ['required', 'in:subscriber,advertiser'],
            'name'          => ['required_if:role,advertiser', 'nullable', 'string', 'max:100'],
            'referral_code' => ['nullable', 'string', 'size:8', 'exists:subscriber_profiles,referral_code'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'              => 'Le numéro de téléphone est requis.',
            'phone.regex'                 => 'Le format du numéro de téléphone est invalide.',
            'role.required'               => 'Le rôle est requis.',
            'role.in'                     => 'Le rôle doit être subscriber ou advertiser.',
            'name.required_if'            => 'Le nom est requis pour un compte annonceur.',
            'name.max'                    => 'Le nom ne peut pas dépasser 100 caractères.',
            'referral_code.size'          => 'Le code de parrainage doit comporter exactement 8 caractères.',
            'referral_code.exists'        => 'Le code de parrainage est invalide.',
        ];
    }
}
