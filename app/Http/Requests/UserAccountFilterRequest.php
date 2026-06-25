<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAccountFilterRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'integer'],
            'tab' => ['nullable', 'string', 'in:all,pending,verified,suspended'],
        ];
    }

    /**
     * The sanitized filters, with empty values normalized and the default tab applied.
     *
     * @return array{search: string|null, year_level: int|null, tab: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->string('search')->toString() ?: null,
            'year_level' => $this->integer('year_level') ?: null,
            'tab' => $this->string('tab')->toString() ?: 'all',
        ];
    }
}
