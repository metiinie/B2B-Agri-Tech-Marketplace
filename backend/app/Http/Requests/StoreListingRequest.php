<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreListingRequest extends FormRequest
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
            'category_id'        => ['nullable', 'integer', 'exists:categories,id'],
            'title'              => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string', 'max:2000'],
            'unit'               => ['required', 'string', 'in:kg,quintal,ton,piece,liter,dozen'],
            'price_per_unit'     => ['required', 'numeric', 'min:0.01'],
            'quantity_available' => ['required', 'numeric', 'min:0'],
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
        ];
    }
}
