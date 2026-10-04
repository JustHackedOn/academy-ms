<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $batchId = $this->route('batch') ? $this->route('batch')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('batches', 'code')->ignore($batchId)],
            'default_fee' => ['required', 'numeric', 'min:0'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:24'],
            'start_date' => ['required', 'date'],
            'status' => ['required', 'in:upcoming,running,completed,archived'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
