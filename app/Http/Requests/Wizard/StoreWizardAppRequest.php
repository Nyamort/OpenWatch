<?php

namespace App\Http\Requests\Wizard;

use App\Models\OrganizationMember;
use Illuminate\Foundation\Http\FormRequest;

class StoreWizardAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        $orgId = $this->integer('organization_id');

        return OrganizationMember::query()
            ->where('organization_id', $orgId)
            ->where('user_id', $this->user()->id)
            ->exists();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'app_name' => ['required', 'string', 'max:255'],
            'env_name' => ['required', 'string', 'max:255'],
            'env_color' => ['nullable', 'string', 'max:20'],
            'env_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
