<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DeviationController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

/*
|--------------------------------------------------------------------------
| Password reset (guests)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Show request form
    Route::get('/forgot-password', fn () => view('auth.forgot-password'))
        ->name('password.request');

    // Send reset link
    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email' => ['required','email']]);
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    })->name('password.email');

    // Show reset form
    Route::get('/reset-password/{token}', function ($token) {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    })->name('password.reset');

    // Handle reset
    Route::post('/reset-password', function (Request $request) {
        $request->validate([
            'token'    => 'required',
            'email'    => ['required','email'],
            'password' => ['required','confirmed','min:12'],
        ]);

        $status = Password::reset(
            $request->only('email','password','password_confirmation','token'),
            function ($user) use ($request) {
                $user->forceFill(['password' => Hash::make($request->password)])
                    ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    })->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Login/Logout
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Protected app (must be logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Land on home after login
    Route::get('/', fn () => redirect()->route('home'))->name('home');

    // Admin area (admins only)
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/', fn () => view('admin.dashboard'))->name('admin.dashboard');

        Route::get('/users',        [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users',       [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');

        Route::patch('/users/{user}/activate',   [\App\Http\Controllers\Admin\UserController::class, 'activate'])->name('admin.users.activate');
        Route::patch('/users/{user}/deactivate', [\App\Http\Controllers\Admin\UserController::class, 'deactivate'])->name('admin.users.deactivate');
        Route::patch('/users/{user}/role',       [\App\Http\Controllers\Admin\UserController::class, 'setRole'])->name('admin.users.role');
        Route::post('/users/{user}/send-reset',  [\App\Http\Controllers\Admin\UserController::class, 'sendReset'])->name('admin.users.sendReset');

        // (Users management routes will be added here
    });

    // App pages
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/prosjekter',  [ProjectsController::class, 'index'])->name('projects.index');
    Route::get('/henting',     [ProjectsController::class, 'henting'])->name('henting.index');
    Route::get('/montering',   [ProjectsController::class, 'montering'])->name('montering.index');
    Route::get('/planlegging', [ProjectsController::class, 'planlegging'])->name('planlegging.index');

    // Avvik
    Route::get  ('/avvik',                          [DeviationController::class, 'index'])->name('avvik.index');
    Route::post ('/avvik',                          [DeviationController::class, 'store'])->name('avvik.store');
    Route::patch('/avvik/{deviation}/resolve',      [DeviationController::class, 'resolve'])->name('avvik.resolve');
    Route::patch('/avvik/{deviation}/resolve-route',[DeviationController::class, 'resolveRoute'])->name('avvik.resolveRoute');

    // Prosjekter: row edits & move
    Route::post ('/prosjekter/{project}/status', [ProjectsController::class, 'setStatus'])->name('projects.status');
    Route::patch('/prosjekter/{project}',        [ProjectsController::class, 'update'])->name('projects.update');
    Route::patch('/projects/{project}/bucket',   [ProjectsController::class, 'moveBucket'])->name('projects.moveBucket');

    // Henting flow
    Route::patch('/projects/{project}/delivered',       [ProjectsController::class, 'markDelivered'])->name('projects.delivered');
    Route::patch('/projects/{project}/ready',           [ProjectsController::class, 'markReady'])->name('projects.ready');
    Route::patch('/projects/{project}/schedule-pickup', [ProjectsController::class, 'schedulePickup'])->name('projects.schedulePickup');
    Route::patch('/projects/{project}/collected',       [ProjectsController::class, 'markCollected'])->name('projects.collected');
    Route::patch('/projects/{project}/contacted',       [ProjectsController::class, 'markContacted'])->name('projects.contacted');

    // Montering flow
    Route::patch('/projects/{project}/mount-start', [ProjectsController::class, 'markMountStart'])->name('projects.mountStart');
    Route::patch('/projects/{project}/mount-done',  [ProjectsController::class, 'markMountDone'])->name('projects.mountDone');
});
