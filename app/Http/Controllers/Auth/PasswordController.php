<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseAuthInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Throwable;

use function is_string;
class PasswordController extends Controller
{
    /**
     * Update the authenticated user's password via Supabase.
     *
     * @throws ValidationException
     */
    public function update(Request $request, SupabaseAuthInterface $supabase): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        // Verify the current password by attempting a Supabase sign-in. A failed
        // sign-in means the supplied current password is incorrect.
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

        // Update the password on Supabase using the user's current session token.
        // Changing the password rotates the Supabase session, so a subsequent
        // attempt with the now-stale token will be rejected and the user is asked
        // to sign in again.
        try {
            $supabase->updatePassword($accessToken, $request->input('password'));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'current_password' => [__('Unable to update password. Please sign in again.')],
            ]);
        }

        return back()->with('status', 'password-updated');
    }
}
