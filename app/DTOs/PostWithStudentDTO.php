<?php

namespace App\DTOs;

/**
 * Data structure for Post and Student models.
 */
readonly class PostWithStudentDTO
{
    public function __construct(
        public int $id,
        public ?string $content,
        public ?string $mood,
        public ?string $status,
        public string $date,
        public string $time,
        public string $section,
        public ?string $anonymous_name,
    ) {
    }
}
