<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->intended('/');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        // Uniform error message to avoid user enumeration
        $error = ValidationException::withMessages([
            'email' => __('Påloggingsdetaljene er feil.'),
        ]);

        if (! Auth::validate($credentials)) {
            throw $error;
        }

        // Also check is_active
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        if (! $user || ! $user->is_active) {
            throw $error;
        }

        // Attempt login (respect "remember me")
        $remember = (bool) $request->boolean('remember');
        if (! Auth::attempt($credentials, $remember)) {
            throw $error;
        }

        // Regenerate session + track last login
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        // Block deactivated accounts
        $active = User::where('email', $credentials['email'])->where('is_active', true)->exists();
        if (! $active) {
            throw ValidationException::withMessages(['email' => 'Kontoen er deaktivert.']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        // Redirect to intended page or home
        return redirect()->intended('/');
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
