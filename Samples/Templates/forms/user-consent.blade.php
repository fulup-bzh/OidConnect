<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <title>BreizhMe OpenIdConnect/OAuth2 Demo</title>

  <link media="all" type="text/css" rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/foundation/5.4.7/css/foundation.min.css">
  <link media="all" type="text/css" rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/foundation/5.4.7/css/normalize.min.css">
  <link media="all" type="text/css" rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/foundicons/3.0.0/foundation-icons.css">
  <link media="all" type="text/css" rel="stylesheet" href="http://oidconnect.breizhme.net/css/gtb-app.css">
  <script src="//cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
  </head>
<body>

<div class="gtb-login-content">
<div class="row">
    <span class="gtb-login-title">User Consent Page</span>
</div>
<div class="gtb-login-inner-zone">
<div class="row">

<div class="small-3 columns right">
    <img style="max-height: 150px; float:right;" src="{{$userinfo['avatar']}}">
</div>

<div class="small-8 columns">

<form data-abide method="POST" action="{{route('user-consent')}}">
    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">

    <div class="name-field">
        <label>User Name <small>required</small>
            <input name="name" value="{{$userinfo['name']}}" type="text" required pattern="([a-zA-Z]|-)+">
        </label>
        <small class="error">Pseudonym is required and must be a string.</small>
    </div>
    <div class="name-field">
        <label>Your Pseudonym <small>required</small>
            <input name="pseudonym" value="{{$userinfo['pseudonym']}}" type="text" required pattern="([a-zA-Z]|-)+">
        </label>
        <small class="error">Pseudonym is required and must be a string.</small>
    </div>
    <div class="email-field">
        <label>Email <small>required</small>
            <input name="email" value="{{$userinfo['email']}}" type="email" required>
        </label>
        <small class="error">An email address is required.</small>
    </div>
    <button class='tiny' type="submit">Submit</button>
</form>


</div>
</div>
</div>
</div>
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/foundation/5.4.7/js/foundation.min.js"></script>
  <script src="http://oidconnect.breizhme.net/js/gtb-app.js"></script>
</body>
</html>