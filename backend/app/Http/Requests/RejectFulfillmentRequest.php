<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'farmer_notes' => ['sometimes', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'farmer_notes.max' => 'Rejection notes must not exceed 1000 characters.',
        ];
    }
}
