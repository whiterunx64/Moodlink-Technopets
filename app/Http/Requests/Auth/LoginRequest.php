<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', PasswordPolicy::rule()],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        if (!Auth::guard('web')->attempt($this->only('email', 'password'))) {
            throw ValidationException::withMessages(['auth_error' => trans('auth.failed')]);
        }
    }
}
