<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'date' => ['required', 'date'],
      'start_time' => ['required', 'date_format:H:i'],
    ];
  }

  public function scheduledAt(): string
  {
    return "{$this->date} {$this->start_time}:00";
  }
}