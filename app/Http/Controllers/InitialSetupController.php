<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InitialSetupController extends Controller
{
    public function create()
    {
        if (User::count() > 0) {
            abort(404);
        }

        return view('auth.initial-setup');
    }

    public function store(Request $request)
    {
        if (User::count() > 0) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => [
                'required',
                'string',
                'regex:/^[A-Za-z]{2}[0-9]{2}$/',
                'unique:users,nickname',
            ],
            'email' => ['required', 'email', 'unique:users,email'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:users,color'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            'email' => $data['email'],
            'color' => $data['color'],
            'role' => 'super_admin',
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('login')
            ->with('status', 'Compte super admin créé. Tu peux te connecter.');
    }
}
