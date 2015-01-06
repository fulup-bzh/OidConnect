<!DOCTYPE html>
<html>
<head lang="fr">
    <meta charset="UTF-8">
    <title></title>
</head>
<body>

<h3>Bonjours {{ $user->pseudonym }}</h3>

Cliquer sur le code ci-après pour réinitialiser votre mot de passe:
<h2><a href="{{ url('/user/security/pwdreset', [$token]) }}">{!! Config::get ('gtb-config.brand') !!}: {!!$token !!}</a></h2>

<p>Ce lien est valide 60mn à date de l'heure d'envoie, passé ce delais vous devez refaire votre demande de réinitialisation.</p>

<br><br>
<span style="color: #808080">Mail est expédié par automate, ne pas répondre. <br>
Si vous avez besoin d'explications complémentaire utiliser le formulaire de contact: <a href="{!! URL::to ('contact') !!}">[ici]</a>
</span>

<br><br>L'administrateur {!! Config::get ('gtb-config.brand') !!}.

</body>
</html>

