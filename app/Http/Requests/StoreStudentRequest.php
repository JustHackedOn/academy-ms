<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'exists:batches,id'],
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => ['required', 'string', 'max:30'],
            'alt_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'fee_type' => ['required', 'in:full,installments'],
            'total_fee' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'join_date' => ['required', 'date'],
            'status' => ['required', 'in:active,dropped,completed,paused'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'installments' => ['required', 'array', 'min:1'],
            'installments.*.due_date' => ['required', 'date'],
            'installments.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
