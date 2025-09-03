<?php

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('translations')->where(function ($query) {
                    return $query->where('locale', $this->get('locale'));
                }),
            ],
            'locale' => [
                'required',
                'string',
                'size:2',
                'alpha',
                'regex:/^[a-zA-Z]{2}$/'
            ],
            'content' => ['required', 'string', 'max:65535'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50', 'alpha_dash'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'The key field is required.',
            'key.unique' => 'A translation with this key already exists for the specified locale.',
            'locale.required' => 'The locale field is required.',
            'locale.size' => 'The locale must be exactly 2 characters.',
            'locale.alpha' => 'The locale may only contain letters.',
            'locale.regex' => 'The locale must be exactly 2 alphabetic characters (e.g., en, fr, es).',
            'content.required' => 'The content field is required.',
            'content.max' => 'Content cannot exceed 65,535 characters.',
            'tags.*.alpha_dash' => 'Tags may only contain letters, numbers, dashes, and underscores.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only process locale if it exists and is a string
        if ($this->has('locale') && is_string($this->locale) && !empty($this->locale)) {
            $this->merge([
                'locale' => strtolower(trim($this->locale)),
            ]);
        }

        // Process tags
        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => array_map('trim', explode(',', $this->tags)),
            ]);
        }
    }
}
