<?php

namespace App\Http\Requests\Wizard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWizardAppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:255'],
            'env_id' => ['required', 'integer', 'exists:environments,id'],
            'env_name' => ['required', 'string', 'max:255'],
            'env_type' => ['required', 'string', 'in:production,staging,development,custom'],
            'env_color' => ['nullable', 'string', 'max:20'],
        ];
    }
}
