<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCapabilityApplicationRequest extends FormRequest
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
            'capability_type'        => ['required', 'string', 'in:farmer,buyer'],
            'supporting_documents'   => ['nullable', 'array'],
            'supporting_documents.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'capability_type.in' => 'Capability type must be either farmer or buyer.',
        ];
    }
}
