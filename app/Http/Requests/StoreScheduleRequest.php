<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
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
            'date'           => ['required', 'date'],
            'start_times'    => ['required', 'array', 'min:1'],
            'start_times.*'  => ['required', 'date_format:H:i'],
        ];
    }

    /** Returns each selected time as a "Y-m-d H:i:s" datetime string. */
    public function scheduledDatetimes(): array
    {
        return array_map(
            fn (string $t) => "{$this->date} {$t}:00",
            $this->start_times,
        );
    }
}