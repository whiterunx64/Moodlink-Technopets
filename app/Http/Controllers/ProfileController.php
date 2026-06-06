<?php

namespace App\Http\Controllers;

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
        $admin = Admin::findByUserId($request->user()->id);

        return Inertia::render('Profile/Settings', [
            'status' => session('status'),
            'admin' => $admin?->profileSummary(),
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
        $admin?->update([
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Email lives on Supabase; only push it when it actually changed.
        if ($validated['email'] !== $user->email && $user instanceof User) {
            try {
                $user->updateSupabaseProfile(['email' => $validated['email']]);
            } catch (Throwable) {
                throw ValidationException::withMessages([
                    'email' => [__('Could not update your email. Please try again.')],
                ]);
            }
        }

        return Redirect::route('profile.show')->with('status', 'profile-updated');
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
