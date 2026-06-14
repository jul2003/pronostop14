<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        $login = trim($request->input('login'));

        $user = User::where('nickname', strtoupper($login))
            ->orWhere('email_pro', $login)
            ->orWhere('email_perso', $login)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'login' => 'Aucun compte ne correspond à cet identifiant.',
            ]);
        }

        $token = Password::broker()->createToken($user);

        $emails = collect([
            $user->email_pro,
            $user->email_perso,
        ])
            ->filter()
            ->unique()
            ->values();

        foreach ($emails as $email) {
            $originalEmail = $user->email;

            $user->forceFill([
                'email' => $email,
            ]);

            $user->sendPasswordResetNotification($token);

            $user->forceFill([
                'email' => $originalEmail,
            ]);
        }

        return back()->with('status', 'Un lien de réinitialisation a été envoyé.');
    }
}
