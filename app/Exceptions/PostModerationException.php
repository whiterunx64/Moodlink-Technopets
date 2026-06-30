<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class PostModerationException extends DomainException
{
    public static function postIsNotSafeAndCannotBeFlagged(): self
    {
        return new self(
            'This post can no longer be flagged because it has already been flagged or moved out of the safe state. '
            . 'Refresh the list to see its current status.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function postIsNotFlaggedAndCannotBeUnflagged(): self
    {
        return new self(
            'This post cannot be unflagged because it is not currently flagged. '
            . 'Refresh the list to see its current status.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
