<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PlayerColorPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InitialSetupController extends Controller
{
    public function create()
    {
        if (User::count() > 0) {
            abort(404);
        }

        return view('auth.initial-setup', [
            'playerColors' => PlayerColorPalette::colors(),
        ]);
    }

    public function store(Request $request)
    {
        if (User::count() > 0) {
            abort(404);
        }

        $request->merge([
            'nickname' => strtoupper((string) $request->input('nickname')),
            'color' => strtoupper((string) $request->input('color')),
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
                'unique:users,color',
            ],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            'email' => $data['email_pro'] ?? $data['email_perso'],
            'email_pro' => $data['email_pro'] ?? null,
            'email_perso' => $data['email_perso'] ?? null,
            'color' => $data['color'],
            'role' => 'super_admin',
            'password' => Hash::make($data['password']),
        ]);

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('admin.index')
            ->with('success', 'Compte super admin créé. Tu es maintenant connecté.');
    }
}
