<?php

namespace App\Http\Requests\Election;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;

class StoreElectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'allowedDomains' => ['required', 'array', 'min:1'],
            'allowedDomains.*' => ['required', 'string', 'max:253', 'regex:/^@?[a-z0-9.-]+\.[a-z]{2,}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'allowedDomains.*.regex' => 'Each allowed domain must look like "school.edu"',
        ];
    }

    /** @return array{title: string, description: string, allowed_domains: array<int, string>} */
    public function electionAttributes(): array
    {
        return [
            'title' => $this->string('title')->trim()->value(),
            'description' => $this->string('description')->value(),
            'allowed_domains' => Election::normaliseDomains($this->input('allowedDomains')),
        ];
    }
}
