<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slot_id' => ['required', 'integer', 'exists:available_schedules,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot_id.required' => 'Please choose an available slot.',
            'slot_id.exists' => 'That slot is no longer available.',
        ];
    }

    /** The chosen available slot to book for this consultation. */
    public function slotId(): int
    {
        return (int) $this->validated('slot_id');
    }
}
