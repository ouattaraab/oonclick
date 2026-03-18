<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'              => ['required', 'string'],
            'code'               => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'type'               => ['required', 'in:registration,login'],
            'device_fingerprint' => ['nullable', 'string', 'max:64'],
            'platform'           => ['nullable', 'in:android,ios,web'],
            'device_model'       => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'    => 'Le numéro de téléphone est requis.',
            'code.required'     => 'Le code OTP est requis.',
            'code.size'         => 'Le code OTP doit comporter exactement 6 chiffres.',
            'code.regex'        => 'Le code OTP doit être composé de 6 chiffres.',
            'type.required'     => 'Le type de vérification est requis.',
            'type.in'           => 'Le type doit être registration ou login.',
            'platform.in'       => 'La plateforme doit être android, ios ou web.',
            'device_model.max'  => 'Le modèle d\'appareil ne peut pas dépasser 100 caractères.',
        ];
    }
}
