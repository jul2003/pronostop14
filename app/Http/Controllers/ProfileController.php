<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

            'email_pro' => [
                'nullable',
                'email',
                'required_without:email_perso',
                Rule::unique('users', 'email_pro')->ignore($user->id),
            ],

            'email_perso' => [
                'nullable',
                'email',
                'required_without:email_pro',
                Rule::unique('users', 'email_perso')->ignore($user->id),
            ],

            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
                Rule::unique('users', 'color')->ignore($user->id),
            ],
        ]);

        $user->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }
}
