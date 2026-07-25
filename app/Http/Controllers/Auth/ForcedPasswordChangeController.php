<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcedPasswordChangeController extends Controller
{
    public function edit(Request $request)
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('home');
        }

        if ($request->session()->has('impersonator_id')) {
            return redirect()->route('pronos.index');
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Le nouveau mot de passe doit être différent du mot de passe temporaire.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', 'Mot de passe mis à jour.');
    }
}
