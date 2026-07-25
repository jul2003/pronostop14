<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Tes accès Pronostop14</title>
</head>
<body style="font-family: Arial, sans-serif; color: #06142F; line-height: 1.5;">
    <p>
        Bonjour {{ $user->name }},
    </p>

    <p>
        Ton compte Pronostop14 vient d’être créé.
    </p>

    <p>
        Voici tes accès :
    </p>

    <table cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
        <tr>
            <td style="font-weight: bold;">Pseudo</td>
            <td>{{ $user->nickname }}</td>
        </tr>

        <tr>
            <td style="font-weight: bold;">
                @if($passwordWasGenerated)
                    Mot de passe généré
                @else
                    Mot de passe
                @endif
            </td>
            <td>{{ $plainPassword }}</td>
        </tr>
    </table>

    <p>
        Ce mot de passe est temporaire. Il devra être changé à ta première connexion.
    </p>

    <p>
        Tu peux te connecter ici :
        <br>
        <a href="{{ route('login') }}">
            {{ route('login') }}
        </a>
    </p>

    <p>
        À bientôt.
    </p>
</body>
</html>
