<?php

namespace App\Http\Controllers;

use App\Contracts\SupabaseAuthInterface;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;
class DeleteAccountController extends Controller
{
    public function delete(Request $request, SupabaseAuthInterface $supabase): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

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
