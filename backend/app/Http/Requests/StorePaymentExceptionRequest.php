<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentExceptionRequest extends FormRequest
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
            'payment_id'  => ['required', 'integer', 'exists:payments,id'],
            'type'        => ['required', 'string', 'in:dispute,mismatch,failed_payment_review,refund_request,other'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_id.exists' => 'The specified payment does not exist.',
            'type.in'           => 'Exception type must be one of: dispute, mismatch, failed_payment_review, refund_request, other.',
            'description.max'   => 'Description must not exceed 2000 characters.',
        ];
    }
}
