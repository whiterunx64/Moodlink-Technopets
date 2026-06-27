<?php

namespace App\Enums;

enum PostMood: string
{
    case Drained = 'Drained';
    case Stressed = 'Stressed';
    case Content = 'Content';
    case Excited = 'Excited';

    /** Tailwind background class used for this mood in charts and badges. */
    public function color(): string
    {
        return match ($this) {
            self::Excited => 'bg-green-400',
            self::Content => 'bg-blue-400',
            self::Stressed => 'bg-yellow-400',
            self::Drained => 'bg-red-400',
        };
    }

    public static function availableMoods(): array
    {
        return self::cases();
    }
}