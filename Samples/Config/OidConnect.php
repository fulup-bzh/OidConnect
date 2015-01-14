<?php

/**
 *  IDP config file for OAuth2/OpenID-Connect Social Authentication
 *
 *  SocialUser  => Object class return to the application after IDP authentication
 * 'IDP-Driver-Name' => [
 *   'name'          // [String] IDP name as presented to enduser
 *   'uid'           // [Number] Free IDP Unique ID in federation table
 *   'provider'      // [Php Class] Provider class implementation
 *   'loa'           // [0-9] Trust Level of assurance [0=not trustedn 1=email-trusted,...]
 *   'client_id'     // [String] what ever the idp provided for your app
 *   'client_secret' // [String] idem client_id
 *   'redirect'      // redirect page after IDP authentication
 *   'logo-img'      // [img 50px] IDP logo in user profile
 *   'login-img'     => [img 250px] Screen shot of IDP authentication/consent page
 *   '????'          => [???] any other field needed by your provider class
 *  ],
 */

$idpImagesDir = '/images/social/';
$redirectDir  = 'http://oidconnect.localnet:8080/auth/';

return [
    // Default Users, Authentication and Provisioning Classes
    // 'socialuser'      => 'OidConnect\UserManagement\SocialUser',
    // 'dbusermodel'     default defined in app/config/auth[model]
    // 'dbfedkeymodel'   => 'OidConnect\Models\FedKeyModel',
    // 'authGuardModel'  => 'OidConnect\LoaAuth\LoaAuthGuard',

    // IDP Provider definition [name, uid, provider] must be present
    // Other parameters may be required depending on Provider implementation


    'github-oauth2' => [
        'name'          => 'github',
        'uid'           => 10001,
        'provider'      => 'OidConnect\IdpDrivers\GitHubProvider',
        'loa'           => 1,  // Email not verified
        'client_id'     => 'xxxxxx',
        'client_secret' => 'xxxxxxxxxxx',
        'redirect'      => $redirectDir . 'github',
        'logo-img'      => $idpImagesDir .'gh-logo.png',
        'login-img'     => $idpImagesDir .'github-consent.png',
        'avatar-img'    => $idpImagesDir .'github-avatar.jpg',
    ],

    'orange-partner' => [
        // https://www.orangepartner.com/content/welcome-to-your-dashboard
        // Warning: Orange does not accept localhost as redirect but will accept oid.localnet that point on 127.0.0.1
        'name'          => 'orange',
        'uid'           => 33002,
        'provider'      => 'OidConnect\IdpDrivers\OrangeProvider',
        'loa'           => 1,  // Email verified
        'client_id'     => 'xxxx',
        'client_secret' => 'xxxxxxxxxxx',
        'application_id'=> 'xxxxxxxxxxxxxxx',
        'redirect'      => $redirectDir . 'orange-login',
        'logo-img'      => $idpImagesDir .'og-logo.png',
        'login-img'     => $idpImagesDir .'orange-auth.png',
        'avatar-img'    => $idpImagesDir .'orange-avatar.jpg',
    ],
    'facebook-connect' => [
        // https://developers.facebook.com/apps
        'name'          => 'facebook',
        'uid'           => 10011,
        'loa'           => 1,  // Email verified by IDP ?
        'provider'      => 'OidConnect\IdpDrivers\FacebookProvider',
        'client_id'     => 'xxxx',
        'client_secret' => 'xxxxxxxxxx',
        'redirect'      => $redirectDir . 'facebook-login',
        'logo-img'      => $idpImagesDir .'fb-logo.png',
        'login-img'     => $idpImagesDir .'facebook-auth.png',
        'avatar-img'    => $idpImagesDir .'facebook-avatar.jpg',
    ],

    'google-oid' => [
        // https://accounts.google.com/ServiceLogin?service=cloudconsole&passive=true&continue=https%3A%2F%2Fconsole.developers.google.com%2Fproject&ltmpl=cloudconsole
        'name'          => 'google',
        'uid'           => 10022,
        'loa'           => 1,  // Email verified by IDP ?
        'provider'      => 'OidConnect\IdpDrivers\GoogleProvider',
        'client_id'     => 'xxxxxxxxx',
        'client_secret' => 'xxxxxxx',
        'client_email'  =>'xxxxxxx',
        'redirect'      => $redirectDir . 'google-login',
        'logo-img'      => $idpImagesDir .'gg-logo.png',
        'login-img'     => $idpImagesDir .'google-auth.png',
        'avatar-img'    => $idpImagesDir .'google-avatar.jpg',
    ],

    'microsoft-live' => [
        // https://account.live.com/developers/applications
        'name'          => 'microsoft',
        'uid'           => 10013,
        'loa'           => 1,  // Email verified by IDP ?
        'provider'      => 'OidConnect\IdpDrivers\MicrosoftProvider',
        'client_id'     => 'xxxx',
        'client_secret' => 'xxxxx',
        'redirect'      => $redirectDir . 'microsoft-login',
        'logo-img'      => $idpImagesDir .'ms-logo.png',
        'login-img'     => $idpImagesDir .'microsoft-auth.png',
        'avatar-img'    => $idpImagesDir .'microsoft-avatar.jpg',
    ],

    'yahoo-oid' => [
        // https://developer.apps.yahoo.com/projects
        'name'          => 'yahoo',
        'uid'           => 10014,
        'loa'           => 1,  // Email verified by IDP ?
        'provider'      => 'OidConnect\IdpDrivers\YahooProvider',
        'client_id'     => 'xxxxxx--',
        'client_secret' => 'xxxx',
        'redirect'      => $redirectDir . 'yahoo-login',
        'logo-img'      => $idpImagesDir .'yh-logo.png',
        'login-img'     => $idpImagesDir .'yahoo-auth.png',
        'avatar-img'    => $idpImagesDir .'yahoo-avatar.jpg',
    ],

    'linkedin-oid' => [
        // https://www.linkedin.com/secure/developer
        'name'          => 'linkedin',
        'uid'           => 10015,
        'loa'           => 1,  // Email verified by IDP ?
        'provider'      => 'OidConnect\IdpDrivers\LinkedInProvider',
        'client_id'     => 'xxxxx',
        'client_secret' => 'xxxxxx',
        'redirect'      => $redirectDir . 'linkedin-login',
        'logo-img'      => $idpImagesDir .'lk-logo.png',
        'login-img'     => $idpImagesDir .'linkedin-auth.png',
        'avatar-img'    => $idpImagesDir .'linkedin-avatar.png',
    ],

];
