<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $users = User::query()
            ->when($q, fn($qq) => $qq->where(function($w) use ($q) {
                $w->where('name','like',"%$q%")->orWhere('email','like',"%$q%");
            }))
            ->orderByRaw("role='admin' desc")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users','q'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required','string','max:255'],
            'email'     => ['required','email','max:255', Rule::unique('users','email')],
            'role'      => ['required', Rule::in(['admin','user'])],
            'is_active' => ['nullable','boolean'],
        ]);

        $user = new User();
        $user->name       = $data['name'];
        $user->email      = $data['email'];
        $user->password   = Hash::make(Str::random(40)); // placeholder
        $user->is_active  = (bool)($data['is_active'] ?? true);
        $user->forceFill(['role' => $data['role']])->save();

        // Send reset-lenke så brukeren setter eget passord
        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('admin.users.index')
            ->with('ok', "Bruker opprettet og tilbakestillingslenke sendt til {$user->email}.");
    }

    public function activate(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('err', 'Du kan ikke aktivere/deaktivere din egen konto her.');
        }
        $user->update(['is_active' => true]);
        return back()->with('ok', "Aktivert: {$user->email}");
    }

    public function deactivate(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('err', 'Du kan ikke deaktivere din egen konto.');
        }
        $user->update(['is_active' => false]);
        return back()->with('ok', "Deaktivert: {$user->email}");
    }

    public function setRole(Request $request, User $user)
    {
        $request->validate(['role' => ['required', Rule::in(['admin','user'])]]);

        // Ikke fjern siste admin
        if ($user->role === 'admin' && $request->role !== 'admin') {
            $otherAdmins = User::where('role','admin')->where('id','!=',$user->id)->count();
            if ($otherAdmins === 0) {
                return back()->with('err', 'Kan ikke fjerne siste admin.');
            }
        }
        // Ikke nedgrader deg selv her
        if (auth()->id() === $user->id && $request->role !== 'admin') {
            return back()->with('err', 'Du kan ikke nedgradere deg selv.');
        }

        $user->forceFill(['role' => $request->role])->save();
        return back()->with('ok', "Rolle oppdatert for {$user->email}.");
    }

    public function sendReset(User $user)
    {
        Password::sendResetLink(['email' => $user->email]);
        return back()->with('ok', "Tilbakestillingslenke sendt til {$user->email}.");
    }
}
