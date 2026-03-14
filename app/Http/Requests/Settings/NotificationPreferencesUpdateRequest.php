<?php

namespace App\Http\Requests\Settings;

use App\Models\UserNotificationPreference;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NotificationPreferencesUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categories' => ['required', 'array'],
            'categories.*.enabled' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $categories = $this->input('categories', []);

            foreach (UserNotificationPreference::LOCKED_CATEGORIES as $locked) {
                if (isset($categories[$locked]) && ! ((bool) $categories[$locked])) {
                    $v->errors()->add(
                        "categories.{$locked}.enabled",
                        "The '{$locked}' notification category cannot be disabled."
                    );
                }
            }
        });
    }
}
