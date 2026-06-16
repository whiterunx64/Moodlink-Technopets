<?php

declare(strict_types=1);

namespace App\Contracts;

interface AvatarStorageInterface
{
    /**
     * Upload a user's avatar image to the avatar bucket and return its public URL.
     *
     * @param string $userId      Owner of the avatar; used to build the object path.
     * @param string $contents    Raw image bytes.
     * @param string $extension   File extension (e.g. jpg, png, webp).
     * @param string $contentType MIME type of the image.
     * @return string Public URL for the stored avatar.
     */
    public function uploadAvatar(string $userId, string $contents, string $extension, string $contentType): string;
}
