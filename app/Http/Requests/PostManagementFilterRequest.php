<?php

namespace App\Http\Requests;

use App\Enums\PostMood;
use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PostManagementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::in(PostStatus::values())],
            'section' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[\w\s\-]+$/', Rule::exists('students', 'section')],
            'mood' => ['sometimes', 'nullable', Rule::in(PostMood::values())],
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $filled = collect(['status', 'section', 'mood'])
                ->filter(fn(string $key) => $this->filled($key))
                ->count();

            if ($filled > 1) {
                $validator->errors()->add('filter', 'Only one filter may be applied at a time.');
            }
        });
    }
}
