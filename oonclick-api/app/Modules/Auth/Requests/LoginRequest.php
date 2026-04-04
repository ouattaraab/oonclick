<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method'             => ['sometimes', 'in:phone,email'],
            'phone'              => ['required_without:email', 'nullable', 'string'],
            'email'              => ['required_without:phone', 'nullable', 'email'],
            'device_fingerprint' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without' => 'Le numéro de téléphone ou l\'adresse e-mail est requis.',
            'email.required_without' => 'L\'adresse e-mail ou le numéro de téléphone est requis.',
        ];
    }
}
