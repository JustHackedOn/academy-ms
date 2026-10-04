<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::in(['cash', 'jazzcash', 'easypaisa', 'bank', 'other'])],
            'for_month' => ['nullable', 'date_format:Y-m'],
            'installment_id' => [
                'nullable',
                'integer',
                Rule::exists('installments', 'id')->where('student_id', $this->input('student_id')),
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'installment_id.exists' => 'The selected installment does not belong to this student.',
        ];
    }
}
