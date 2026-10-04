<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
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
            'remove_photo' => ['nullable', 'boolean'],
            'phone' => ['required', 'string', 'max:30'],
            'alt_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:active,dropped,completed,paused'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Optional fee adjustments:
            'total_fee' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
