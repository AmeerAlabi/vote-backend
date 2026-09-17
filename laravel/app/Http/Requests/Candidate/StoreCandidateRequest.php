<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string'],
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Please provide all required fields (name, bio, and photo)',
            'photo.mimes' => 'Only JPEG, JPG, and PNG images are allowed',
            'photo.image' => 'Only JPEG, JPG, and PNG images are allowed',
            'photo.max' => 'Photo must be 5MB or smaller',
        ];
    }
}
