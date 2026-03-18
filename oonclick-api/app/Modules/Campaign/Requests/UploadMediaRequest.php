<?php

namespace App\Modules\Campaign\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media'     => ['required', 'file', 'mimes:mp4,mov,avi,webm,jpg,jpeg,png,gif', 'max:102400'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'media.required'     => 'Le fichier média est requis.',
            'media.file'         => 'Le média doit être un fichier valide.',
            'media.mimes'        => 'Le média doit être au format : mp4, mov, avi, webm, jpg, jpeg, png ou gif.',
            'media.max'          => 'Le média ne peut pas dépasser 100 Mo.',
            'thumbnail.file'     => 'La miniature doit être un fichier valide.',
            'thumbnail.mimes'    => 'La miniature doit être au format : jpg, jpeg ou png.',
            'thumbnail.max'      => 'La miniature ne peut pas dépasser 5 Mo.',
        ];
    }
}
