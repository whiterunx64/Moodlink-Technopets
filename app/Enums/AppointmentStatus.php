<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'Pending';
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Rejected = 'Rejected';
}