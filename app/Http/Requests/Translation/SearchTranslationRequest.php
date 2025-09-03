<?php

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

class SearchTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', 'size:2', 'alpha'],
            'tags' => ['sometimes', 'string'],
            'key' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
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
            $tags = array_map('trim', explode(',', $this->tags));
            $this->merge(['tags' => $tags]);
        }
    }
}
