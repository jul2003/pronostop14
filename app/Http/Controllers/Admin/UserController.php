<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    //
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
        if (!auth()->user()->isSuperAdmin()) {
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
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => [
                'required',
                'string',
                'regex:/^[A-Za-z]{2}[0-9]{2}$/',
                'unique:users,nickname',
            ],
            //'email' => ['required', 'email', 'unique:users,email'],
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
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'role' => ['required', Rule::in(['player', 'admin'])],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create([
            'name' => $data['name'],
            'nickname' => strtoupper($data['nickname']),
            //'email' => $data['email'],
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
            ->with('success', 'Tu saisis maintenant les pronos de '.$user->display_name.'.');
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
