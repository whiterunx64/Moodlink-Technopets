<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AvatarStorageInterface;
use DomainException;
use Override;
use Psr\Log\LoggerInterface;

use function in_array;
final class AvatarStorage implements AvatarStorageInterface
{
    /**
     * MIME types accepted for avatar images.
     */
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly SupabaseClient $client,
        private readonly LoggerInterface $logger,
        private readonly string $bucket,
    ) {
    }

    /**
     * @throws DomainException when the image type is not an accepted avatar format.
     */
    #[Override]
    public function uploadAvatar(string $userId, string $contents, string $extension, string $contentType): string
    {
        $this->ensureSupportedImageType($contentType);

        $path = $this->avatarPath($userId, $extension);

        $this->putObject($path, $contents, $contentType);

        return $this->publicUrl($path);
    }

    private function putObject(string $path, string $contents, string $contentType): void
    {
        // Override the client's default application/json so the raw bytes are
        // stored (and served) with the correct MIME type; x-upsert overwrites
        // any existing object at the stable per-user path.
        $this->client->request(
            'POST',
            "/storage/v1/object/{$this->bucket}/{$path}",
            [
                'headers' => [
                    'Content-Type' => $contentType,
                    'x-upsert' => 'true',
                ],
                'body' => $contents,
            ],
            useServiceKey: true,
        );

        $this->logger->info('Avatar upload successful', ['path' => $path]);
    }

    private function avatarPath(string $userId, string $extension): string
    {
        return "{$userId}/avatar.{$extension}";
    }

    private function publicUrl(string $path): string
    {
        return "{$this->client->getUrl()}/storage/v1/object/public/{$this->bucket}/{$path}";
    }

    // ── Private Guards ────────────────────────────────────────────────────────

    private function ensureSupportedImageType(string $contentType): void
    {
        if (!in_array($contentType, self::ALLOWED_MIME_TYPES, true)) {
            throw new DomainException("Unsupported avatar image type: {$contentType}.");
        }
    }
}