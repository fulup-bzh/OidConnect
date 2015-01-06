<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title></title>
</head>
<body>

<h3>Hello {{ $pseudonym }}</h3>

<p>Your created an account on {!! Config::get ('gtb-config.brand') !!}, unfortunately the social network you've federated cannot certify your email address.</p>

References for data to validate.
<ul>
    <li>Email: {!! $email !!}</li>
    <li>Pseudonym: {!! $pseudonym !!}</li>
</ul>

<p>Click on following link to confirm your personal information</p>
<h2><a href="{!! URL::to (Route('email-check-code'). "?&key=" . $checkcode) !!}">{!! Config::get ('gtb-config.brand') !!}: {!!$checkcode !!}</a></h2>

<br><br>
<span style="color: #808080">This mail is sent by an automate, do not respond. <br>
If you need further information please use email contact form at: <a href="{!! URL::to ('contact') !!}">[ici]</a>
</span>

<br><br>Web Admin {!! Config::get ('gtb-config.brand') !!}.

</body>
</html>