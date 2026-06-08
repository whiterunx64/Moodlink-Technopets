<?php

namespace App\Http\Requests;

use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStudentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(StudentStatus::class)],
        ];
    }

    /** The validated target status as an enum. */
    public function targetStatus(): StudentStatus
    {
        return StudentStatus::from($this->validated('status'));
    }
}
