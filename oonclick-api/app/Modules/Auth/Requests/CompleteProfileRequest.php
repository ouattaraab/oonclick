<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:50'],
            'last_name'     => ['required', 'string', 'max:50'],
            'gender'        => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'city'          => ['required', 'string', 'max:100'],
            'operator'      => ['nullable', 'in:mtn,moov,orange,other'],
            'interests'     => ['nullable', 'array', 'max:10'],
            'interests.*'   => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'    => 'Le prénom est requis.',
            'first_name.max'         => 'Le prénom ne peut pas dépasser 50 caractères.',
            'last_name.required'     => 'Le nom de famille est requis.',
            'last_name.max'          => 'Le nom de famille ne peut pas dépasser 50 caractères.',
            'gender.required'        => 'Le genre est requis.',
            'gender.in'              => 'Le genre doit être male, female ou other.',
            'date_of_birth.required' => 'La date de naissance est requise.',
            'date_of_birth.date'     => 'La date de naissance est invalide.',
            'date_of_birth.before'   => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'date_of_birth.after'    => 'La date de naissance doit être postérieure au 01/01/1900.',
            'city.required'          => 'La ville est requise.',
            'city.max'               => 'La ville ne peut pas dépasser 100 caractères.',
            'operator.required'      => 'L\'opérateur mobile est requis.',
            'operator.in'            => 'L\'opérateur doit être mtn, moov, orange ou other.',
            'interests.array'        => 'Les centres d\'intérêt doivent être une liste.',
            'interests.max'          => 'Vous ne pouvez pas sélectionner plus de 10 centres d\'intérêt.',
            'interests.*.string'     => 'Chaque centre d\'intérêt doit être une chaîne de caractères.',
            'interests.*.max'        => 'Chaque centre d\'intérêt ne peut pas dépasser 50 caractères.',
        ];
    }
}
