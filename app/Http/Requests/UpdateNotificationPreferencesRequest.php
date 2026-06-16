<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array'],
            'notifications.newFlags' => ['required', 'boolean'],
            'notifications.appointments' => ['required', 'boolean'],
            'notifications.escalations' => ['required', 'boolean'],
            'notifications.weeklyReports' => ['required', 'boolean'],
            'notifications.systemUpdates' => ['required', 'boolean'],
        ];
    }
}
