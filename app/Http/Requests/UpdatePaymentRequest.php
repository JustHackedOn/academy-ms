<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::in(['cash', 'jazzcash', 'easypaisa', 'bank', 'other'])],
            'for_month' => ['nullable', 'date_format:Y-m'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
