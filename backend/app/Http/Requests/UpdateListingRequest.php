<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
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
            'category_id'        => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'title'              => ['sometimes', 'string', 'max:255'],
            'description'        => ['sometimes', 'nullable', 'string', 'max:2000'],
            'unit'               => ['sometimes', 'string', 'in:kg,quintal,ton,piece,liter,dozen'],
            'price_per_unit'     => ['sometimes', 'numeric', 'min:0.01'],
            'quantity_available' => ['sometimes', 'numeric', 'min:0'],
            'status'             => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit.in'               => 'Unit must be one of: kg, quintal, ton, piece, liter, dozen.',
            'price_per_unit.min'    => 'Price per unit must be at least 0.01.',
            'quantity_available.min' => 'Quantity available cannot be negative.',
            'status.in'             => 'Status must be either active or inactive.',
        ];
    }
}
