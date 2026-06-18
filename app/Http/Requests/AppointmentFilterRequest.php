<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentFilterRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'tab' => ['nullable', 'string', 'in:requests,scheduled,history,rejected'],
    ];
  }

  public function filters(): array
  {
    return [
      'tab' => $this->string('tab')->toString() ?: 'requests',
    ];
  }
}
