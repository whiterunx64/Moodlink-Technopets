<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAccountFilterRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'search' => ['nullable', 'string', 'max:255'],
      'year_level' => ['nullable', 'integer'],
      'tab' => ['nullable', 'string', 'in:All,Pending,Verified,Suspended'],
    ];
  }

  public function filters(): array
  {
    return [
      'search' => $this->string('search')->toString() ?: null,
      'year_level' => $this->integer('year_level') ?: null,
      'tab' => $this->string('tab')->toString() ?: 'All',
    ];
  }
}