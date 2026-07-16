<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'first_name'  => ['required', 'string', 'max:255'],
            'second_name' => ['required', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'regex:/^\+251[0-9]{9}$/', 'unique:users,phone'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'code'        => ['required', 'string', 'digits:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be in +251XXXXXXXXX format.',
            'phone.unique' => 'An account with this phone number already exists.',
        ];
    }
}
