<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
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
            'phone'   => ['required', 'string', 'regex:/^\+251[0-9]{9}$/'],
            'purpose' => ['required', 'string', 'in:registration,login'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be in +251XXXXXXXXX format.',
        ];
    }
}
