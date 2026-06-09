<?php

namespace App\Queries;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class BuildPostQuery
{
    private const PER_PAGE = 15;

    private function baseQuery(): Builder
    {
        return Post::query()
            ->with('student')
            ->fromVerifiedStudents();
    }

    private function applySortOrder(Builder $query, string $sort): Builder
    {
        return $sort === 'oldest' ? $query->orderBy('datetime') : $query->orderByDesc('datetime');
    }

    public function listLatest(string $sort = 'latest'): LengthAwarePaginator
    {
        return $this->applySortOrder($this->baseQuery(), $sort)
            ->paginate(self::PER_PAGE);
    }

    public function listBySection(string $section, string $sort = 'latest'): LengthAwarePaginator
    {
        return $this->applySortOrder(
            $this->baseQuery()->whereHas('student', fn (Builder $q) =>
                $q->verified()->where('section', $section)
            ),
            $sort
        )->paginate(self::PER_PAGE);
    }

    public function listByMood(string $mood, string $sort = 'latest'): LengthAwarePaginator
    {
        return $this->applySortOrder(
            $this->baseQuery()->where('mood', $mood),
            $sort
        )->paginate(self::PER_PAGE);
    }

    public function listByStatus(string $status, string $sort = 'latest'): LengthAwarePaginator
    {
        return $this->applySortOrder(
            $this->baseQuery()->where('status', $status),
            $sort
        )->paginate(self::PER_PAGE);
    }
}