<?php

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('translations')->where(function ($query) {
                    return $query->where('locale', $this->locale);
                })->ignore($this->route('translation')),
            ],
            'locale' => ['sometimes', 'string', 'size:2', 'alpha'],
            'content' => ['sometimes', 'string', 'max:65535'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50', 'alpha_dash'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.unique' => 'A translation with this key already exists for the specified locale.',
            'locale.size' => 'Locale must be exactly 2 characters (e.g., en, fr, es).',
            'locale.alpha' => 'Locale must contain only letters.',
            'content.max' => 'Content cannot exceed 65,535 characters.',
            'tags.*.alpha_dash' => 'Tags may only contain letters, numbers, dashes, and underscores.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('locale')) {
            $this->merge([
                'locale' => strtolower($this->locale),
            ]);
        }

        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => array_map('trim', explode(',', $this->tags)),
            ]);
        }
    }
}
