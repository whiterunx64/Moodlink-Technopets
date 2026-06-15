<?php

declare(strict_types=1);

namespace App\Traits;

trait HasInitials
{
    protected function getInitialsFromName(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = collect($words)->map(fn(string $word) => $word[0] ?? '')->take(2)->implode('');

        return strtoupper($initials);
    }
}
