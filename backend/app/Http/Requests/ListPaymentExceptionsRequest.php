<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPaymentExceptionsRequest extends FormRequest
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
            'status'   => ['sometimes', 'string', 'in:open,investigating,resolved,rejected'],
            'type'     => ['sometimes', 'string', 'in:dispute,mismatch,failed_payment_review,refund_request,other'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in'    => 'Invalid exception status filter.',
            'type.in'      => 'Invalid exception type filter.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page must not exceed 100.',
        ];
    }
}
