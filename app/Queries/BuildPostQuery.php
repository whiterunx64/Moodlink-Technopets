<?php

namespace App\Queries;

use App\Models\BuildPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BuildPostQuery
{
    public function listLatest(): Collection
    {
        return BuildPost::query()
            ->with('student')
            ->fromVerifiedStudents()
            ->orderByDesc('datetime')
            ->get();
    }

    public function listBySection(string $section): Collection
    {
        return BuildPost::query()
            ->with('student')
            ->whereHas('student', fn(Builder $q) => $q->verified()->where('section', $section))
            ->orderByDesc('datetime')
            ->get();
    }

    public function listByMood(string $mood): Collection
    {
        return BuildPost::query()
            ->with('student')
            ->where('mood', $mood)
            ->fromVerifiedStudents()
            ->orderByDesc('datetime')
            ->get();
    }

    public function listByStatus(string $status): Collection
    {
        return BuildPost::query()
            ->with('student')
            ->where('status', $status)
            ->fromVerifiedStudents()
            ->orderByDesc('datetime')
            ->get();
    }

    public function listBetweenDates(string $from, string $until): Collection
    {
        return BuildPost::query()
            ->with('student')
            ->fromVerifiedStudents()
            ->whereBetween('datetime', [$from, $until])
            ->orderByDesc('datetime')
            ->get();
    }
}
