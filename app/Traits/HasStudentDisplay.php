<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\YearLevel;
use App\Models\Appointment;

trait HasStudentDisplay
{
    public function displayStudentName(): string
    {
        return $this->student?->name ?? 'Unknown'; 
    }

    public function displayStudentSection(): string
    {
        return $this->student?->section ?? ''; 
    }

    public function studentProfile(): array
    {
        if ($this->student === null) {
            return [
                'initials' => '',
                'section' => '',
                'year_level' => '',
                'student_id' => '',
                'total_appointments' => 0,
                'history' => [], 
            ];
        }

        $initials = collect(explode(' ', trim($this->student->name)))
            ->filter() // remove empty words
            ->map(fn ($word) => strtoupper($word[0] ?? '')) // first letter uppercase
            ->take(2) // only first two words
            ->implode(''); // join initials

        return [
            'initials' => $initials, // computed initials

            'section' => $this->student->section, // student section

            'year_level' => YearLevel::tryFrom($this->student->year_level)?->toOrdinal() ?? '', // enum readable

            'student_id' => $this->student->student_number, // student ID

            'total_appointments' => $this->student->appointments->count(), // total appointments

            'history' => $this->student->appointments
                ->sortByDesc('datetime') // latest first
                ->map(fn (Appointment $appointment) => [
                    'context' => $appointment->context, // type
                    'date' => $appointment->displayDate(), // formatted date
                    'time' => $appointment->displayTime(), // formatted time
                    'note' => $appointment->note, // note
                    'status' => $appointment->status->value, // status
                ])
                ->values() // reset keys
                ->all(), // to array
        ];
    }
}