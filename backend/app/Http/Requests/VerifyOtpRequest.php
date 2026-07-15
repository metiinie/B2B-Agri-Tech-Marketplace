<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'code'    => ['required', 'string', 'digits:6'],
            'purpose' => ['required', 'string', 'in:registration,login'],
            'name'    => ['required_if:purpose,registration', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex'        => 'Phone must be in +251XXXXXXXXX format.',
            'name.required_if'   => 'Name is required for registration.',
        ];
    }
}
