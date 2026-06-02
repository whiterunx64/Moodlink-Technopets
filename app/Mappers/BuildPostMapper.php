<?php

namespace App\Mappers;

use App\DTOs\PostWithStudentDTO;
use App\Models\BuildPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class BuildPostMapper
{
    public function toDTO(BuildPost $post): PostWithStudentDTO
    {
        // Timezone-aware Carbon parse for Manila time display
        $carbon = Carbon::parse($post->datetime)->setTimezone('Asia/Manila');

        return new PostWithStudentDTO(
            id: $post->id,
            content: $post->content ?? null,
            mood: $post->mood ?? null,
            status: $post->status,
            date: $carbon->format('Y-m-d'),
            time: $carbon->format('h:i A'),
            section: $post->student->section,
            anonymous_name: $post->student->anonymous_name ?? null,
        );
    }

    public function toDTOCollection(Collection $posts): array
    {
        // Map each post model into a typed DTO
        return $posts->map(fn(BuildPost $post) => $this->toDTO($post))->values()->all();
    }
}