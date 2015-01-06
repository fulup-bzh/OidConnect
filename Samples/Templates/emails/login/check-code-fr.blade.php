<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title></title>
</head>
<body>

<h3>Bonjours {{ $pseudonym }}</h3>

<p>Vous avez déclaré un compte et un email sur {!! Config::get ('gtb-config.brand') !!}, mais celui ci n'est pas certifié par le réseau social que vous utilisez.</p>

Références des informations à vérifier.
<ul>
    <li>Email: {!! $email !!}</li>
    <li>Pseudonym: {!! $pseudonym !!}</li>
</ul>

<p>Cliquer sur le code ci-après pour confirmer vos informations</p>
<h2><a href="{!! URL::to (Route('email-check-code'). "?&key=" . $checkcode) !!}">{!! Config::get ('gtb-config.brand') !!}: {!!$checkcode !!}</a></h2>

<br><br>
<span style="color: #808080">Ce mail est expédié par automate, ne pas sélectionner: répondre. <br>
Si vous avez besoin d'explications complémentaire utiliser le formulaire de contact: <a href="{!! URL::to ('contact') !!}">[ici]</a>
</span>

<br><br>L'administrateur {!! Config::get ('gtb-config.brand') !!}.

</body>
</html>