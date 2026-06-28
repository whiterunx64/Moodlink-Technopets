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
            'program' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[\w\s\-]+$/', Rule::exists('students', 'program')],
            'mood' => ['sometimes', 'nullable', Rule::enum(PostMood::class)],
            'sort' => ['sometimes', 'nullable', Rule::in(['latest', 'oldest'])],
            'tab' => ['sometimes', 'nullable', 'string', Rule::in(['reported', 'archives'])],
        ];
    }
    /**
     * Return the validated filter values as a typed array with defaults.
     */
    public function filters(): array
    {
        return [
            'status'  => $this->validated('status'),
            'program' => $this->validated('program'),
            'mood'    => $this->validated('mood'),
            'sort'    => $this->validated('sort') ?? 'latest',
            'tab'     => $this->validated('tab'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $filled = collect(['status', 'program', 'mood'])
                ->filter(fn(string $key) => $this->filled($key))
                ->count();

            if ($filled > 1) {
                $validator->errors()->add('filter', 'Only one filter may be applied at a time.');
            }
        });
    }
}