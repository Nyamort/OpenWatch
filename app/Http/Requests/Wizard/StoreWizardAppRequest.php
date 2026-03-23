<?php

namespace App\Http\Requests\Wizard;

use Illuminate\Foundation\Http\FormRequest;

class StoreWizardAppRequest extends FormRequest
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
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'app_name' => ['required', 'string', 'max:255'],
            'app_slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'env_name' => ['required', 'string', 'max:255'],
            'env_slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'env_type' => ['required', 'string', 'in:production,staging,development,custom'],
            'env_color' => ['nullable', 'string', 'max:20'],
            'env_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
