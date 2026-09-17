<?php

namespace App\Http\Requests\Election;

use Illuminate\Foundation\Http\FormRequest;

class UpdateElectionStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'active.required' => 'Active status must be a boolean',
            'active.boolean' => 'Active status must be a boolean',
        ];
    }
}
