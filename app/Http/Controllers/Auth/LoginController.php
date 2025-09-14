<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;                
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            // If already logged in, send admins to admin area, others to home
            return auth()->user()->role === 'admin'
                ? redirect()->intended(route('admin.users.index'))
                : redirect()->intended('/');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1) Validate inputs
        $credentials = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        // Uniform error (no user enumeration)
        $error = ValidationException::withMessages([
            'email' => __('Påloggingsdetaljene er feil.'),
        ]);

        // 2) Try to authenticate (respect remember)
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw $error;
        }

        // 3) Regenerate session AFTER successful login
        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $request->user();

        // 4) Block deactivated accounts
        if (! $user->is_active) {              // use 'active' if that's your column
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Kontoen er deaktivert.',
            ]);
        }

        // 5) Record last login
        $user->forceFill(['last_login_at' => now()])->save();

        // 6) Redirect based on role
        return $user->role === 'admin'
            ? redirect()->intended(route('admin.users.index'))
            : redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    protected function redirectTo($request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
