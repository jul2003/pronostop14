<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PlayerColorPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PlayerProfileController extends Controller
{
    public function edit(
        Request $request
    ) {
        $user =
            $request->user();

        $playerColors =
            $this
                ->playerColors();

        $usedPlayerColors =
            User::query()
                ->where(
                    'id',
                    '!=',
                    $user->id
                )
                ->whereNotNull(
                    'color'
                )
                ->get([
                    'nickname',
                    'color',
                ])
                ->mapWithKeys(
                    fn (
                        User $player
                    ) => [
                        strtoupper(
                            (string)
                            $player->color
                        ) =>
                            $player->nickname,
                    ]
                )
                ->all();

        return view(
            'profile.player',
            [
                'playerColors' =>
                    $playerColors,

                'usedPlayerColors' =>
                    $usedPlayerColors,
            ]
        );
    }

    public function update(
        Request $request
    ) {
        $user =
            $request->user();

        $request->merge([
            'nickname' =>
                strtoupper(
                    (string)
                    $request->input(
                        'nickname'
                    )
                ),

            'color' =>
                strtoupper(
                    (string)
                    $request->input(
                        'color'
                    )
                ),
        ]);

        $data =
            $request->validate(
                [
                    'nickname' => [
                        'required',
                        'string',
                        'regex:/^[A-Z]{2}[0-9]{2}$/',

                        Rule::unique(
                            'users',
                            'nickname'
                        )->ignore(
                            $user->id
                        ),
                    ],

                    'email_pro' => [
                        'nullable',
                        'email',
                        'required_without:email_perso',

                        Rule::unique(
                            'users',
                            'email_pro'
                        )->ignore(
                            $user->id
                        ),
                    ],

                    'email_perso' => [
                        'nullable',
                        'email',
                        'required_without:email_pro',

                        Rule::unique(
                            'users',
                            'email_perso'
                        )->ignore(
                            $user->id
                        ),
                    ],

                    'color' => [
                        'required',
                        'string',

                        Rule::in(
                            $this
                                ->playerColors()
                        ),

                        Rule::unique(
                            'users',
                            'color'
                        )->ignore(
                            $user->id
                        ),
                    ],

                    /*
                     * Ces champs sont présents sur
                     * le formulaire actuel grâce aux
                     * inputs hidden + checkbox.
                     *
                     * "sometimes" permet toutefois
                     * aux anciennes requêtes et aux
                     * tests qui ne les envoient pas
                     * de continuer à fonctionner.
                     */
                    'notify_new_prediction' => [
                        'sometimes',
                        'boolean',
                    ],

                    'notify_results_available' => [
                        'sometimes',
                        'boolean',
                    ],

                    'notify_prediction_reminder' => [
                        'sometimes',
                        'boolean',
                    ],

                    'current_password' => [
                        'nullable',
                        'required_with:password',
                        'current_password',
                    ],

                    'password' => [
                        'nullable',
                        'confirmed',
                        Password::defaults(),
                    ],
                ],
                [
                    'color.in' =>
                        'Cette couleur n’est pas dans la palette autorisée.',

                    'color.unique' =>
                        'Cette couleur est déjà utilisée par un autre joueur.',
                ]
            );

        if (
            ! empty(
                $data['password']
            )
        ) {
            $data['password'] =
                Hash::make(
                    $data['password']
                );
        } else {
            unset(
                $data['password']
            );
        }

        unset(
            $data[
                'current_password'
            ]
        );

        unset(
            $data[
                'password_confirmation'
            ]
        );

        /*
         * Le champ email central reste
         * l'adresse utilisée par Laravel
         * pour envoyer les notifications.
         */
        $data['email'] =
            $data['email_pro']
            ?? $data['email_perso']
            ?? null;

        $user->update(
            $data
        );

        return back()->with(
            'success',
            'Profil mis à jour.'
        );
    }

    private function playerColors(): array
    {
        return collect(
            PlayerColorPalette::colors()
        )
            ->map(
                fn ($color) =>
                    strtoupper(
                        (string)
                        $color
                    )
            )
            ->values()
            ->all();
    }
}
