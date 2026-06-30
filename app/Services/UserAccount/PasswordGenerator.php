<?php

declare(strict_types=1);

namespace App\Services\UserAccount;

use function count;
use function strlen;

class PasswordGenerator
{
    public function generateInitialPassword(int $length = 8): string
    {
        // random 8 character one lowercase letter, maximum two uppercase letters, and one symbol.
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $symbols = '!@$^-';

        $all = $lower;

        $chars = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
        ];

        // Add remaining characters
        while (count($chars) < $length - 1) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle all variables
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        $symbol = $symbols[random_int(0, strlen($symbols) - 1)];

        return implode('', $chars) . $symbol;
    }
}