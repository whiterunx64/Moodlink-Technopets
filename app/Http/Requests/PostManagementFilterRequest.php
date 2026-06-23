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
            'status' => ['sometimes', 'nullable', Rule::enum(PostStatus::class)],
            'section' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[\w\s\-]+$/', Rule::exists('students', 'section')],
            'mood' => ['sometimes', 'nullable', Rule::enum(PostMood::class)],
            'sort' => ['sometimes', 'nullable', Rule::in(['latest', 'oldest'])],
        ];
    }
    /**
     * Return the validated filter values as a typed array with defaults.
     */
    public function filters(): array
    {
        return [
            'status' => $this->validated('status'),
            'section' => $this->validated('section'),
            'mood' => $this->validated('mood'),
            'sort' => $this->validated('sort') ?? 'latest',
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