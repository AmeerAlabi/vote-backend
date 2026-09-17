<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Password must be at least 8 characters long',
        ];
    }
}
