<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Services\ProfileService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin profile updates, Supabase password management,
 * and account deletion for the authenticated user.
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $service,
    ) {
    }

    public function settings(Request $request): Response
    {
        return Inertia::render('Profile/Settings', [
            'status' => session('status'),
            ...$this->service->getSettingsData($request->user()),
        ]);
    }

    public function updateMetadata(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            $this->service->updatePersonalDetails($request->user(), $request->validated());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['profile' => [$exception->getMessage()]]);
        }

        return Redirect::route('profile.settings')->with('status', 'profile-updated');
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $notifications = $request->validated('notifications');

        try {
            $this->service->updateNotificationPreferences($request->user(), $notifications);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['notifications' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'preferences-updated');
    }
    
    public function updateAvatar(UploadAvatarRequest $request): RedirectResponse
    {
        try {
            $this->service->uploadAndStoreAvatar($request->user(), $request->file('avatar'));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['avatar' => [$exception->getMessage()]]);
        }

        return Redirect::route('profile.settings')->with('status', 'avatar-updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $currentPassword = $request->validated('current_password');
        $newPassword = $request->validated('password');

        try {
            $this->service->changeAccountPassword(
                user: $request->user(),
                currentPassword: $currentPassword,
                newPassword: $newPassword,
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['current_password' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'password-updated');
    }

    public function destroyAccount(DeleteAccountRequest $request): RedirectResponse
    {
        $password = $request->validated('password');

        try {
            $this->service->deleteUserAccount($request->user(), $password);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['password' => [$exception->getMessage()]]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
