<?php

namespace App\Http\Controllers;

use App\Contracts\AvatarStorageInterface;
use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseAuthInterface;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

use function is_string;
/**
 * Handles admin profile updates, Supabase password management,
 * and account deletion for the authenticated user.
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile settings page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $admin = Admin::findByUserId($user->id);

        $notifications = [];

        if ($user instanceof User) {
            $notifications = $user->getPreference('notifications', []);
        }

        return Inertia::render('Profile/Settings', [
            'status' => session('status'),
            'admin' => $admin?->profileSummary(),
            'notifications' => $notifications,
        ]);

    }

    /**
     * Update the user's personal details.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $admin = Admin::findByUserId($user->id);

        if ($admin !== null) {
            $admin->update([
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'phone' => $validated['phone'] ?? null,
                // Keep the existing avatar when the request doesn't carry one.
                'avatar' => $validated['avatar'] ?? $admin->avatar,
            ]);
        }

        if ($user instanceof User) {
            $this->syncSupabaseProfile($user, $validated);
        }

        return Redirect::route('profile.show')->with('status', 'profile-updated');
    }

    /**
     * @throws ValidationException
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notifications' => ['required', 'array'],
            'notifications.newFlags' => ['required', 'boolean'],
            'notifications.appointments' => ['required', 'boolean'],
            'notifications.escalations' => ['required', 'boolean'],
            'notifications.weeklyReports' => ['required', 'boolean'],
            'notifications.systemUpdates' => ['required', 'boolean'],
        ]);

        $user = $request->user();

        if ($user instanceof User) {
            try {
                $user->updatePreferences(['notifications' => $validated['notifications']]);
            } catch (Throwable) {
                throw ValidationException::withMessages([
                    'notifications' => [__('Could not save your preferences. Please try again.')],
                ]);
            }
        }

        return back()->with('status', 'preferences-updated');
    }

    /**
     * @throws ValidationException
     */
    public function updateAvatar(Request $request, AvatarStorageInterface $avatarStorage): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $file = $request->file('avatar');
        $extension = $file->extension() ?: $file->getClientOriginalExtension();

        try {
            $url = $avatarStorage->uploadAvatar(
                $user->id,
                (string) file_get_contents($file->getRealPath()),
                $extension,
                $file->getMimeType() ?? 'application/octet-stream',
            );
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'avatar' => [__('Could not upload your avatar. Please try again.')],
            ]);
        }

        // Cache-bust so browsers fetch the new image despite the stable path.
        $url .= '?v=' . time();

        Admin::findByUserId($user->id)?->update(['avatar' => $url]);

        if ($user instanceof User) {
            try {
                $user->updateSupabaseProfile(['data' => ['avatar_url' => $url]]);
            } catch (Throwable) {
            }
        }

        return Redirect::route('profile.show')->with('status', 'avatar-updated');
    }

    /**
     * Push profile fields that live on the Supabase auth record.
     * 
     * @throws ValidationException
     */
    private function syncSupabaseProfile(User $user, array $validated): void
    {
        $payload = [];

        if ($validated['email'] !== $user->email) {
            $payload['email'] = $validated['email'];
        }

        $metadata = array_filter([
            'phone' => $validated['phone'] ?? null,
            'full_name' => trim("{$validated['firstName']} {$validated['lastName']}"),
            'avatar_url' => $validated['avatar'] ?? null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');

        if ($metadata !== []) {
            $payload['data'] = $metadata;
        }

        if ($payload === []) {
            return;
        }

        try {
            $user->updateSupabaseProfile($payload);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'email' => [__('Could not update your profile. Please try again.')],
            ]);
        }
    }

    /**
     * Update the authenticated user's password via Supabase.
     *
     * @throws ValidationException
     */
    public function updatePassword(Request $request, SupabaseAuthInterface $supabase): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        // Check current password using Supabase sign-in.
        try {
            $supabase->signIn($user->email, $request->input('current_password'));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'current_password' => [trans('auth.password')],
            ]);
        }

        $accessToken = $user instanceof SupabaseAuthenticatable
            ? $user->getAccessToken()
            : null;

        if (!is_string($accessToken)) {
            throw ValidationException::withMessages([
                'current_password' => [__('Unable to update password. Please sign in again.')],
            ]);
        }

        // Update password via Supabase session token.
        // Changing password invalidates the current session.
        try {
            $supabase->updatePassword($accessToken, $request->input('password'));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'current_password' => [__('Unable to update password. Please sign in again.')],
            ]);
        }

        return back()->with('status', 'password-updated');
    }

    /**
     * Delete the authenticated user's account.
     *
     * @throws ValidationException
     */
    public function destroy(Request $request, SupabaseAuthInterface $supabase): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        // Confirm identity before destroying the account.
        try {
            $supabase->signIn($user->email, $request->input('password'));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $admin = Admin::where('user_id', $user->id)->first();

        if ($admin) {
            $admin->update(['status' => 'inactive']);
            $admin->delete();
        }

        $supabase->deleteUser($user->id);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
