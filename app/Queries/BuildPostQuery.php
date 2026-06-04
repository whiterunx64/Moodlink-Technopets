<?php

namespace App\Queries;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BuildPostQuery
{
    private function baseQuery(): Builder
    {
        return Post::query()
            ->with('student')
            ->fromVerifiedStudents();
    }

    public function listLatest(): Collection
    {
        return $this->baseQuery()
            ->orderByDesc('datetime')
            ->get();
    }

    public function listBySection(string $section): Collection
    {
        return $this->baseQuery()
            ->whereHas('student', fn (Builder $q) =>
                $q->verified()->where('section', $section)
            )
            ->orderByDesc('datetime')
            ->get();
    }

    public function listByMood(string $mood): Collection
    {
        return $this->baseQuery()
            ->where('mood', $mood)
            ->orderByDesc('datetime')
            ->get();
    }

    public function listByStatus(string $status): Collection
    {
        return $this->baseQuery()
            ->where('status', $status)
            ->orderByDesc('datetime')
            ->get();
    }
}