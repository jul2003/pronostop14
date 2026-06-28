<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PlayerColorPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByRaw("
            CASE role
                WHEN 'super_admin' THEN 1
                WHEN 'admin' THEN 2
                ELSE 3
            END
        ")
            ->orderBy('nickname')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return back()->withErrors([
                'role' => 'Le super admin ne peut pas être modifié.',
            ]);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'player'])],
        ]);

        $user->update([
            'role' => $data['role'],
        ]);

        return back()->with('success', 'Rôle mis à jour.');
    }

    public function create()
    {
        return view('admin.users.create', [
            'playerColors' => PlayerColorPalette::colors(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'color' => strtoupper((string) $request->input('color')),
            'nickname' => strtoupper((string) $request->input('nickname')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'nickname' => [
                'required',
                'string',
                'regex:/^[A-Z]{2}[0-9]{2}$/',
                'unique:users,nickname',
            ],

            'email_pro' => [
                'nullable',
                'email',
                'required_without:email_perso',
                'unique:users,email_pro',
            ],

            'email_perso' => [
                'nullable',
                'email',
                'required_without:email_pro',
                'unique:users,email_perso',
            ],

            'color' => [
                'required',
                'string',
                Rule::in(PlayerColorPalette::colors()),
            ],

            'role' => ['required', Rule::in(['player', 'admin'])],

            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            'email' => $data['email_pro'] ?? $data['email_perso'],
            'email_pro' => $data['email_pro'] ?? null,
            'email_perso' => $data['email_perso'] ?? null,
            'color' => $data['color'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé.');
    }

    public function impersonate(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        abort_if($user->isSuperAdmin(), 403);

        session([
            'impersonator_id' => auth()->id(),
        ]);

        Auth::login($user);

        return redirect()
            ->route('pronos.index')
            ->with('success', 'Tu saisis maintenant les pronos de ' . $user->display_name . '.');
    }

    public function stopImpersonating()
    {
        abort_unless(session()->has('impersonator_id'), 403);

        $impersonator = User::findOrFail(session('impersonator_id'));

        session()->forget('impersonator_id');

        Auth::login($impersonator);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Retour au compte super admin.');
    }
}
