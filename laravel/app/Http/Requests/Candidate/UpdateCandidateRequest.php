<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'bio' => ['sometimes', 'required', 'string'],
            'photo' => ['sometimes', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.mimes' => 'Only JPEG, JPG, and PNG images are allowed',
            'photo.image' => 'Only JPEG, JPG, and PNG images are allowed',
            'photo.max' => 'Photo must be 5MB or smaller',
        ];
    }
}
