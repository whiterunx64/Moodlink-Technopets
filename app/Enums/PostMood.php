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

    /** Wellbeing weight (higher is better) used to plot a mood on the trend chart. */
    public function wellbeingScore(): float
    {
        return match ($this) {
            self::Excited => 4.0,
            self::Content => 3.0,
            self::Stressed => 2.0,
            self::Drained => 1.0,
        };
    }

    public static function availableMoods(): array
    {
        return self::cases();
    }
}