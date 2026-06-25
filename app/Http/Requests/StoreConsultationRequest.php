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
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
        ];
    }

    /** The validated date and start time combined into a "Y-m-d H:i:s" string. */
    public function scheduledAt(): string
    {
        return "{$this->date} {$this->start_time}:00";
    }
}
