<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
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
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
            'quantity'   => ['required', 'numeric', 'min:0.001'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'listing_id.exists' => 'The selected listing does not exist.',
            'quantity.min'      => 'Quantity must be at least 0.001.',
        ];
    }
}
