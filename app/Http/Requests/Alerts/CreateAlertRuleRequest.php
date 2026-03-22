<?php

namespace App\Http\Requests\Alerts;

use App\Actions\Alerts\CreateAlertRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAlertRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'metric' => ['required', 'string', Rule::in(CreateAlertRule::ALLOWED_METRICS)],
            'operator' => ['required', 'string', Rule::in(CreateAlertRule::ALLOWED_OPERATORS)],
            'threshold' => ['required', 'numeric'],
            'window_minutes' => ['required', 'integer', Rule::in(CreateAlertRule::ALLOWED_WINDOWS)],
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
