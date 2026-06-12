<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

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
            'email' => ['required', 'email', 'unique:users,email'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'role' => ['required', Rule::in(['player', 'admin'])],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create([
            'name' => $data['name'],
            'nickname' => strtoupper($data['nickname']),
            'email' => $data['email'],
            'color' => $data['color'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé.');
    }
}
