<?php

namespace App\Http\Requests\Issues;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateIssuesRequest extends FormRequest
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
            'issue_ids' => ['required', 'array', 'min:1', 'max:100'],
            'issue_ids.*' => ['required', 'integer'],
            'action' => ['required', 'string', Rule::in(['resolve', 'ignore', 'reopen'])],
        ];
    }
}
