<?php

namespace App\Http\Requests;

use App\Enums\PostMood;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostManagementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mood' => ['sometimes', Rule::in(PostMood::values())],
            'section' => ['sometimes', 'string', 'max:100', 'regex:/^[\w\s\-]+$/', Rule::exists('students', 'section')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'mood' => $this->route('mood'),
            'section' => $this->route('section'),
        ]));
    }
}
