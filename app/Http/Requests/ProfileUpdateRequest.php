<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Normalize the phone number into canonical PH international form
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (is_string($phone) && trim($phone) !== '') {
            // Strip everything except digits.
            $digits = preg_replace('/\D/', '', $phone);

            // Normalize the various local prefixes to a bare 10-digit.
            $national = match (true) {
                str_starts_with($digits, '63') => substr($digits, 2),   
                str_starts_with($digits, '0') => substr($digits, 1),   
                default => $digits,                                    
            };

            $this->merge(['phone' => '+63' . $national]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Name and phone live on the admins table; email is owned by the
     * Supabase-managed auth.users record. Role is not self-editable.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            // PH mobile in canonical international form: +63 followed by a 10-digit
            // number starting with 9 (e.g. +639171234567).
            'phone' => ['nullable', 'string', 'regex:/^\+639\d{9}$/'],
            'avatar' => ['nullable', 'string', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Philippine mobile number, e.g. 09171234567.',
        ];
    }
}
