<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PlayerProfileController extends Controller
{
    public function edit()
    {
        return view('profile.player');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nickname' => [
                'required',
                'string',
                'regex:/^[A-Za-z]{2}[0-9]{2}$/',
                Rule::unique('users', 'nickname')->ignore($user->id),
            ],
            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
                Rule::unique('users', 'color')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['current_password']);
        unset($data['password_confirmation']);

        $data['email'] = $data['email_pro'] ?? $data['email_perso'];

        $user->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }
}
