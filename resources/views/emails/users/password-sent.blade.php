<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ton nouveau mot de passe Pronostop14</title>
</head>
<body style="font-family: Arial, sans-serif; color: #06142F; line-height: 1.5;">
    <p>
        Bonjour {{ $user->name }},
    </p>

    <p>
        Un nouveau mot de passe temporaire Pronostop14 vient d’être généré pour ton compte.
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
            <td style="font-weight: bold;">Nouveau mot de passe</td>
            <td>{{ $plainPassword }}</td>
        </tr>
    </table>

    <p>
        Ce mot de passe est temporaire. Il devra être changé à ta prochaine connexion.
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
