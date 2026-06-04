<?php

namespace App\Mappers;

use App\DTOs\PostWithStudentDTO;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class BuildPostMapper
{
    /**
     * Converts a Post model and its related Student data into a structured PostWithStudentDTO
     * 
     * @param Post $post The Post model to convert
     * @return PostWithStudentDTO The resulting structured DTO object
     */
    public function toDTO(Post $post): PostWithStudentDTO
    {
        $carbon = Carbon::parse($post->datetime)->setTimezone('Asia/Manila');

        return new PostWithStudentDTO(
            id: $post->id,
            content: $post->content,
            mood: $post->mood?->value, // TODO, FIX THE BUG
            status: $post->status?->value,
            date: $carbon->format('Y-m-d'),
            time: $carbon->format('h:i A'),
            section: $post->student->section,
            anonymous_name: $post->student->anonymous_name,
        );
    }

    public function toDTOCollection(Collection $posts): array
    {
        return $posts->map(fn(Post $post) => $this->toDTO($post))->values()->all();
    }
}
