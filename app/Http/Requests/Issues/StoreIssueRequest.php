<?php

namespace App\Http\Requests\Issues;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:500'],
            'fingerprint' => ['required', 'string', 'size:64'],
            'type' => ['nullable', 'string', Rule::in(['exception', 'performance', 'other'])],
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'critical'])],
            'source_type' => ['nullable', 'string', Rule::in(['exception', 'request', 'job'])],
            'trace_id' => ['nullable', 'string', 'max:36'],
            'group_key' => ['nullable', 'string', 'max:64'],
            'execution_id' => ['nullable', 'string', 'max:36'],
            'snapshot' => ['nullable', 'array'],
        ];
    }
}
