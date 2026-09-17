<?php

namespace App\Http\Requests\Election;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;

class UpdateElectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'allowedDomains' => ['sometimes', 'required', 'array', 'min:1'],
            'allowedDomains.*' => ['required', 'string', 'max:253', 'regex:/^@?[a-z0-9.-]+\.[a-z]{2,}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'allowedDomains.*.regex' => 'Each allowed domain must look like "school.edu"',
        ];
    }

    /** Only the attributes actually present in the request. */
    public function electionAttributes(): array
    {
        $attributes = [];

        if ($this->has('title')) {
            $attributes['title'] = $this->string('title')->trim()->value();
        }
        if ($this->has('description')) {
            $attributes['description'] = $this->string('description')->value();
        }
        if ($this->has('allowedDomains')) {
            $attributes['allowed_domains'] = Election::normaliseDomains($this->input('allowedDomains'));
        }

        return $attributes;
    }
}
