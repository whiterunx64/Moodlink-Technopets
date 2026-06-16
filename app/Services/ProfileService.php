<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AvatarStorageInterface;
use App\Contracts\SupabaseAuthInterface;
use App\Models\Admin;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Throwable;

use function is_string;

class ProfileService
{
    public function __construct(
        private readonly SupabaseAuthInterface $auth,
        private readonly AvatarStorageInterface $avatarStorage,
    ) {
    }

    public function getSettingsData(User $user): array
    {
        $admin = Admin::findByUserId($user->id);

        return [
            'admin' => $admin?->profileSummary(),
            'notifications' => $user->getPreference('notifications', []),
        ];
    }

    /**
     * @throws DomainException when the Supabase profile sync fails.
     */
    public function updatePersonalDetails(User $user, array $data): void
    {
        $admin = Admin::findByUserId($user->id);
        $admin?->savePersonalDetails($data);

        $this->syncProfileToSupabase($user, $data);
    }

    /**
     * @throws DomainException when the preferences can't be saved.
     */
    public function updateNotificationPreferences(User $user, array $notifications): void
    {
        try {
            $user->updatePreferences(['notifications' => $notifications]);
        } catch (Throwable $exception) {
            throw new DomainException('Could not save your preferences. Please try again.', previous: $exception);
        }
    }

    /**
     * @throws DomainException when the upload fails.
     */
    public function uploadAndStoreAvatar(User $user, UploadedFile $file): void
    {
        $avatarUrl = $this->uploadAvatarToStorage($user, $file);

        $admin = Admin::findByUserId($user->id);
        $admin?->saveAvatarUrl($avatarUrl);

        $this->mirrorAvatarToSupabase($user, $avatarUrl);
    }

    /**
     * @throws DomainException when the current password is wrong or the update fails.
     */
    public function changeAccountPassword(User $user, string $currentPassword, string $newPassword): void
    {
        $this->verifyCurrentPassword($user, $currentPassword);

        $accessToken = $user->getAccessToken();

        if (!is_string($accessToken)) {
            throw new DomainException('Unable to update password. Please sign in again.');
        }

        $this->applyNewPassword($accessToken, $newPassword);
    }

    /**
     * @throws DomainException when the current password is wrong.
     */
    public function deleteUserAccount(User $user, string $password): void
    {
        $this->verifyCurrentPassword($user, $password);

        $admin = Admin::findByUserId($user->id);
        $admin?->deactivateAndDelete();

        $this->auth->deleteUser($user->id);
    }

    /**
     * @throws DomainException when the Supabase update fails.
     */
    private function syncProfileToSupabase(User $user, array $data): void
    {
        // Email is managed by Supabase auth and is not editable here, so only
        // name, phone and avatar are mirrored into user_metadata, which Supabase
        // merges. Each is included only when present, so a blank never wipes it.
        $metadata = [];

        if (($data['phone'] ?? '') !== '') {
            $metadata['phone'] = $data['phone'];
        }

        $fullName = trim("{$data['firstName']} {$data['lastName']}");

        if ($fullName !== '') {
            $metadata['full_name'] = $fullName;
        }

        if (($data['avatar'] ?? '') !== '') {
            $metadata['avatar_url'] = $data['avatar'];
        }

        // Nothing to mirror.
        if ($metadata === []) {
            return;
        }

        try {
            $user->updateSupabaseProfile(['data' => $metadata]);
        } catch (Throwable $exception) {
            throw new DomainException('Could not update your profile. Please try again.', previous: $exception);
        }
    }

    private function mirrorAvatarToSupabase(User $user, string $avatarUrl): void
    {
        try {
            $user->updateSupabaseProfile(['data' => ['avatar_url' => $avatarUrl]]);
        } catch (Throwable) {
            // Local avatar is saved; metadata re-syncs on the next profile save.
        }
    }

    /**
     * @throws DomainException when the upload fails.
     */
    private function uploadAvatarToStorage(User $user, UploadedFile $file): string
    {
        $extension = $file->extension() ?: $file->getClientOriginalExtension();
        $contents = (string) file_get_contents($file->getRealPath());
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        try {
            $publicUrl = $this->avatarStorage->uploadAvatar($user->id, $contents, $extension, $mimeType);
        } catch (Throwable $exception) {
            throw new DomainException('Could not upload your avatar. Please try again.', previous: $exception);
        }

        return $publicUrl . '?v=' . time();
    }

    /**
     * @throws DomainException when Supabase rejects the new password.
     */
    private function applyNewPassword(string $accessToken, string $newPassword): void
    {
        // Changing the password invalidates the current Supabase session.
        try {
            $this->auth->updatePassword($accessToken, $newPassword);
        } catch (Throwable $exception) {
            throw new DomainException('Unable to update password. Please sign in again.', previous: $exception);
        }
    }

    private function verifyCurrentPassword(User $user, string $password): void
    {
        try {
            $this->auth->signIn($user->email, $password);
        } catch (Throwable $exception) {
            throw new DomainException(trans('auth.password'), previous: $exception);
        }
    }
}
