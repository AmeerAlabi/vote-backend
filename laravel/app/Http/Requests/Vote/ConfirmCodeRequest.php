<?php

namespace App\Http\Requests\Vote;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCodeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sessionId' => ['required', 'string', 'uuid'],
            'code' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'sessionId.required' => 'Session ID and verification code are required',
            'code.required' => 'Session ID and verification code are required',
        ];
    }
}
