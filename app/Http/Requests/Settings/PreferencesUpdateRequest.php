<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PreferencesUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'locale' => ['nullable', 'string', 'in:en,fr'],
            'display_preferences' => ['nullable', 'array'],
        ];
    }
}
