<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListFulfillmentsRequest extends FormRequest
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
            'status'   => ['sometimes', 'string', 'in:pending,accepted,rejected,completed,cancelled'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in'    => 'Invalid fulfillment status filter.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page must not exceed 50.',
        ];
    }
}
