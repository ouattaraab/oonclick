<?php

namespace App\Modules\Campaign\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                  => ['nullable', 'string', 'max:150'],
            'description'            => ['nullable', 'string', 'max:1000'],
            'format'                 => ['nullable', 'in:video,scratch,quiz,flash'],
            'budget'                 => ['nullable', 'integer', 'min:5000'],
            'cost_per_view'          => ['nullable', 'integer', 'min:100', 'max:500'],
            'starts_at'              => ['nullable', 'date', 'after:now'],
            'ends_at'                => ['nullable', 'date', 'after:starts_at'],
            'targeting'              => ['nullable', 'array'],
            'targeting.cities'       => ['nullable', 'array'],
            'targeting.cities.*'     => ['string', 'max:100'],
            'targeting.genders'      => ['nullable', 'array'],
            'targeting.genders.*'    => ['in:male,female,other'],
            'targeting.age_min'      => ['nullable', 'integer', 'min:16', 'max:99'],
            'targeting.age_max'      => ['nullable', 'integer', 'min:16', 'max:99', 'gte:targeting.age_min'],
            'targeting.operators'    => ['nullable', 'array'],
            'targeting.operators.*'  => ['in:mtn,moov,orange,other'],
            'targeting.interests'    => ['nullable', 'array'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $id       = $this->route('id');
                $campaign = Campaign::find($id);

                if ($campaign && $campaign->status !== 'draft') {
                    $validator->errors()->add(
                        'status',
                        'Seules les campagnes en brouillon peuvent être modifiées.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'title.max'                    => 'Le titre ne peut pas dépasser 150 caractères.',
            'description.max'              => 'La description ne peut pas dépasser 1000 caractères.',
            'format.in'                    => 'Le format doit être : video, scratch, quiz ou flash.',
            'budget.integer'               => 'Le budget doit être un entier.',
            'budget.min'                   => 'Le budget minimum est de 5 000 FCFA.',
            'cost_per_view.integer'        => 'Le coût par vue doit être un entier.',
            'cost_per_view.min'            => 'Le coût par vue minimum est de 100 FCFA.',
            'cost_per_view.max'            => 'Le coût par vue maximum est de 500 FCFA.',
            'starts_at.date'               => 'La date de début est invalide.',
            'starts_at.after'              => 'La date de début doit être dans le futur.',
            'ends_at.date'                 => 'La date de fin est invalide.',
            'ends_at.after'                => 'La date de fin doit être après la date de début.',
            'targeting.array'              => 'Le ciblage doit être un tableau.',
            'targeting.cities.array'       => 'Les villes doivent être un tableau.',
            'targeting.cities.*.string'    => 'Chaque ville doit être une chaîne de caractères.',
            'targeting.cities.*.max'       => 'Le nom d\'une ville ne peut pas dépasser 100 caractères.',
            'targeting.genders.array'      => 'Les genres doivent être un tableau.',
            'targeting.genders.*.in'       => 'Chaque genre doit être : male, female ou other.',
            'targeting.age_min.integer'    => 'L\'âge minimum doit être un entier.',
            'targeting.age_min.min'        => 'L\'âge minimum est 16 ans.',
            'targeting.age_min.max'        => 'L\'âge minimum ne peut pas dépasser 99 ans.',
            'targeting.age_max.integer'    => 'L\'âge maximum doit être un entier.',
            'targeting.age_max.min'        => 'L\'âge maximum est 16 ans.',
            'targeting.age_max.max'        => 'L\'âge maximum ne peut pas dépasser 99 ans.',
            'targeting.age_max.gte'        => 'L\'âge maximum doit être supérieur ou égal à l\'âge minimum.',
            'targeting.operators.array'    => 'Les opérateurs doivent être un tableau.',
            'targeting.operators.*.in'     => 'Chaque opérateur doit être : mtn, moov, orange ou other.',
            'targeting.interests.array'    => 'Les centres d\'intérêt doivent être un tableau.',
        ];
    }
}
