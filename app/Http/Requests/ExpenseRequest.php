<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in([
                'Utilities', 'Rent', 'Salaries', 'Marketing', 'Software', 'Equipment', 'Miscellaneous',
            ])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'spent_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'Choose one of the supported expense categories.',
        ];
    }
}
