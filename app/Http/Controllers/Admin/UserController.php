<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewUserCredentialsMail;
use App\Mail\UserPasswordSentMail;
use App\Models\AppSetting;
use App\Models\User;
use App\Support\PlayerColorPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount([
            'seasons',
            'pronos',
            'journeeScores',
        ])
            ->orderByRaw("
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

    public function create()
    {
        $playerColors = PlayerColorPalette::colors();

        $usedPlayerColors = User::query()
            ->whereNotNull('color')
            ->get(['nickname', 'color'])
            ->mapWithKeys(fn (User $user) => [
                strtoupper((string) $user->color) => $user->nickname,
            ])
            ->all();

        return view('admin.users.create', [
            'playerColors' => $playerColors,
            'usedPlayerColors' => $usedPlayerColors,
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
                Rule::unique('users', 'color'),
            ],
            'role' => ['required', Rule::in(['player', 'admin'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'color.unique' => 'Cette couleur est déjà utilisée par un autre joueur.',
        ]);

        $passwordWasGenerated = blank($data['password'] ?? null);

        $plainPassword = $passwordWasGenerated
            ? $this->generateTemporaryPassword()
            : $data['password'];

        $mailRecipients = collect([
            $data['email_pro'] ?? null,
            $data['email_perso'] ?? null,
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        try {
            DB::transaction(function () use ($data, $plainPassword, $passwordWasGenerated, $mailRecipients) {
                $user = User::create([
                    'name' => $data['name'],
                    'nickname' => $data['nickname'],
                    'email' => $data['email_pro'] ?? $data['email_perso'],
                    'email_pro' => $data['email_pro'] ?? null,
                    'email_perso' => $data['email_perso'] ?? null,
                    'color' => $data['color'],
                    'role' => $data['role'],
                    'password' => Hash::make($plainPassword),
                    'must_change_password' => true,
                    'is_active' => true,
                ]);

                Mail::to($mailRecipients)->send(
                    new NewUserCredentialsMail(
                        user: $user,
                        plainPassword: $plainPassword,
                        passwordWasGenerated: $passwordWasGenerated,
                    )
                );
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'password' => 'Utilisateur non créé : impossible d’envoyer le mail avec le mot de passe.',
                ]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé. Le mot de passe a été envoyé par mail et devra être changé à la première connexion.');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $playerColors = PlayerColorPalette::colors();
        $usedPlayerColors = $this->usedPlayerColorsExcept($user);
        $deletionBlockers = $this->deletionBlockers($user);

        return view('admin.users.edit', [
            'editedUser' => $user,
            'playerColors' => $playerColors,
            'usedPlayerColors' => $usedPlayerColors,
            'deletionBlockers' => $deletionBlockers,
            'canBeDeleted' => empty($deletionBlockers),
            'showPasswordEmailButton' => $this->showPasswordEmailButton(),
            'passwordEmailRecipients' => $this->mailRecipientsForUser($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

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
            'role' => ['nullable', Rule::in(['player', 'admin'])],
        ], [
            'color.unique' => 'Cette couleur est déjà utilisée par un autre joueur.',
        ]);

        $userData = [
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            'email' => $data['email_pro'] ?? $data['email_perso'],
            'email_pro' => $data['email_pro'] ?? null,
            'email_perso' => $data['email_perso'] ?? null,
            'color' => $data['color'],
        ];

        if (! $user->isSuperAdmin() && $user->id !== auth()->id()) {
            $userData['role'] = $data['role'] ?? $user->role;
        }

        $user->update($userData);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function sendPasswordByEmail(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($this->showPasswordEmailButton(), 403);

        $mailRecipients = $this->mailRecipientsForUser($user);

        if (empty($mailRecipients)) {
            return back()->withErrors([
                'password_email' => 'Aucune adresse email n’est configurée pour cet utilisateur.',
            ]);
        }

        $plainPassword = $this->generateTemporaryPassword();

        try {
            DB::transaction(function () use ($user, $plainPassword, $mailRecipients) {
                $user->forceFill([
                    'password' => Hash::make($plainPassword),
                    'must_change_password' => true,
                ])->save();

                Mail::to($mailRecipients)->send(
                    new UserPasswordSentMail(
                        user: $user,
                        plainPassword: $plainPassword,
                    )
                );
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'password_email' => 'Impossible d’envoyer le mail avec le nouveau mot de passe. Le mot de passe n’a pas été modifié.',
            ]);
        }

        return back()->with(
            'success',
            'Nouveau mot de passe envoyé par email à '.implode(', ', $mailRecipients).'.'
        );
    }

    public function updateRole(Request $request, User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($user->isSuperAdmin()) {
            return back()->withErrors([
                'role' => 'Le super admin ne peut pas être modifié.',
            ]);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'role' => 'Tu ne peux pas modifier ton propre rôle.',
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

    public function deactivate(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($user->isSuperAdmin()) {
            return back()->withErrors([
                'user' => 'Le super admin ne peut pas être désactivé.',
            ]);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'user' => 'Tu ne peux pas désactiver ton propre compte.',
            ]);
        }

        $user->forceFill([
            'is_active' => false,
        ])->save();

        return back()->with('success', 'Utilisateur désactivé. Son historique reste conservé.');
    }

    public function reactivate(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($user->isSuperAdmin()) {
            return back()->withErrors([
                'user' => 'Le super admin est toujours actif.',
            ]);
        }

        $user->forceFill([
            'is_active' => true,
        ])->save();

        return back()->with('success', 'Utilisateur réactivé.');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($user->isSuperAdmin()) {
            return back()->withErrors([
                'user' => 'Le super admin ne peut pas être supprimé.',
            ]);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'user' => 'Tu ne peux pas supprimer ton propre compte.',
            ]);
        }

        $deletionBlockers = $this->deletionBlockers($user);

        if (! empty($deletionBlockers)) {
            return back()->withErrors([
                'user' => 'Suppression impossible : cet utilisateur possède déjà un historique. Désactive-le plutôt.',
            ]);
        }

        $displayName = $user->display_name;

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur '.$displayName.' supprimé.');
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

    private function usedPlayerColorsExcept(User $currentUser): array
    {
        return User::query()
            ->where('id', '!=', $currentUser->id)
            ->whereNotNull('color')
            ->get(['nickname', 'color'])
            ->mapWithKeys(fn (User $user) => [
                strtoupper((string) $user->color) => $user->nickname,
            ])
            ->all();
    }

    private function deletionBlockers(User $user): array
    {
        $blockers = [];

        if ($user->seasons()->exists()) {
            $blockers[] = 'rattachement à au moins une saison';
        }

        if ($user->pronos()->exists()) {
            $blockers[] = 'pronos existants';
        }

        if ($user->journeeScores()->exists()) {
            $blockers[] = 'scores de journées existants';
        }

        return $blockers;
    }

    private function generateTemporaryPassword(): string
    {
        $symbols = ['!', '?', '#', '@', '%', '+'];

        return str_shuffle(
            Str::random(8)
            .random_int(1000, 9999)
            .$symbols[array_rand($symbols)]
        );
    }

    private function mailRecipientsForUser(User $user): array
    {
        return collect([
            $user->email_pro,
            $user->email_perso,
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function showPasswordEmailButton(): bool
    {
        $setting = AppSetting::where('key', 'show_password_email_button')->first();

        if (! $setting) {
            return true;
        }

        return (bool) $setting->typedValue();
    }
}
