<?php

namespace App\Http\Controllers;

use App\Support\PlayerColorPalette;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerProfileController extends Controller
{
    public function edit()
    {
        return view('profile.player', [
            'playerColors' => PlayerColorPalette::colors(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'nickname' => strtoupper((string) $request->input('nickname')),
            'color' => strtoupper((string) $request->input('color')),
        ]);

        $data = $request->validate([
            'nickname' => [
                'required',
                'string',
                'regex:/^[A-Z]{2}[0-9]{2}$/',
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
                'string',
                Rule::in(PlayerColorPalette::colors()),
                Rule::unique('users', 'color')->ignore($user->id),
            ],
        ]);

        $data['email'] = $data['email_pro'] ?? $data['email_perso'] ?? null;

        $user->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }
}
