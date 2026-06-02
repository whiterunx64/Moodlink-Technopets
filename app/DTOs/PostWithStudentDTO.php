<?php

namespace App\DTOs;

readonly class PostWithStudentDTO
{
    public function __construct(
        public int     $id,
        public ?string $content,
        public ?string $mood,
        public string  $status,
        public string  $date,
        public string  $time,
        public string  $section,
        public ?string $anonymous_name,
    ) {}
}
