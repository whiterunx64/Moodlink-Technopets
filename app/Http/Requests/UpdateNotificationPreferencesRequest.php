<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array'],
            'notifications.new_flags' => ['required', 'boolean'],
            'notifications.appointments' => ['required', 'boolean'],
            'notifications.escalations' => ['required', 'boolean'],
            'notifications.weekly_reports' => ['required', 'boolean'],
            'notifications.system_updates' => ['required', 'boolean'],
        ];
    }
}
