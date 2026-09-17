<?php

namespace App\Http\Requests\Vote;

use Illuminate\Foundation\Http\FormRequest;

class CastVoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'candidateId' => ['required', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return ['candidateId.required' => 'Candidate ID is required'];
    }
}
