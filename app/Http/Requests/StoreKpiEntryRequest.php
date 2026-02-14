<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpiEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Implement your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'kpi_template_id' => ['required', 'exists:kpi_templates,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'entry_date' => ['required', 'date'],
            'target' => ['required', 'numeric', 'min:0'],
            'actual' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['OK', 'NG', 'PENDING'])],
            'dynamic_fields' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'kpi_template_id.required' => 'KPI template is required.',
            'kpi_template_id.exists' => 'Selected KPI template does not exist.',
            'department_id.required' => 'Department is required.',
            'department_id.exists' => 'Selected department does not exist.',
            'entry_date.required' => 'Entry date is required.',
            'entry_date.date' => 'Entry date must be a valid date.',
            'target.required' => 'Target value is required.',
            'target.numeric' => 'Target must be a number.',
            'actual.numeric' => 'Actual must be a number.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: OK, NG, PENDING.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Business Rule: If status is NG, warn about CAPA requirement
            if ($this->status === 'NG') {
                // This doesn't fail validation but can be checked in controller
                $this->merge(['requires_capa' => true]);
            }
        });
    }
}
