<?php

namespace App\Enums;

enum PostStatus: string
{
    case Flagged = 'flagged';
    case Safe = 'safe';
    case Archived = 'archived';
}