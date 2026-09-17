<?php

namespace App\Http\Requests\Vote;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['email.required' => 'Email is required'];
    }

    public function email(): string
    {
        return strtolower(trim($this->string('email')->value()));
    }
}
