<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SignupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Admin already exists',
            'password.min' => 'Password must be at least 8 characters long',
        ];
    }
}
