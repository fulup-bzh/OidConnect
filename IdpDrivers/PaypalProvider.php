<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : Paypal OpenID-Connect OAth2 provider.
 * Note   : Paypal is compliant with OpenID connect and this provider
 *          is  the parent class reference for every other OpenID connect provider.
 *          You should enable "LogIn with Paypal" on developper dashbord console
 *          and click "Avanced options" to authorize email and username.
 *
 * Reference:
 *  Dashboard: https://developer.paypal.com/webapps/developer/applications/createapp
 *  Documents: https://developer.paypal.com/docs/api/
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


class PaypalProvider extends _DriverSuperClass {

    // main IDP configuration URLs
    protected $openidconnect = true;
    protected $oidsubject    = 'user_id'; // paypal does not use "sub" it should be "user_id" !!!

    // Paypal used different URL for sandbox and production
    public function __construct ($app, $config, $fedKeyModel, $socialUser) {

        if ($config['sandbox']) {
            $this->authTokenUrl   = 'https://www.sandbox.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize';
            $this->accessTokenUrl = 'https://api.sandbox.paypal.com/v1/identity/openidconnect/tokenservice';
            $this->identityApiUrl = 'https://api.sandbox.paypal.com/v1/identity/openidconnect/userinfo/?schema=openid';
        } else {
            $this->authTokenUrl   = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize';
            $this->accessTokenUrl = 'https://api.paypal.com/v1/identity/openidconnect/tokenservice';
            $this->identityApiUrl = 'https://api.paypal.com/v1/identity/openidconnect/userinfo/?schema=openid';
        }

        parent::__construct ($app, $config, $fedKeyModel, $socialUser);
    }

    // OAuth2 action-1:  getAuthUrl($state) build authorization token url
    protected $scopes      = ['openid','profile','email'];

    // OAuth2 action-2: getAccessToken($code) request access token remove basic auth from header
    // use default basic auth header from parent class

    // OAuth2 action-3: getUserByToken($tokens) request User attributes from IDP's Rest API
    protected $uidtoken     = 'id_token'; // slot name of user's uid within OpenID-Connect tokens

    protected function normalizeprofile ($paypalprofile) 	{

        // Paypal profile is quite basic !!!
        $normedprofile = [
            'loa'       => $this->loa,
            'name'   => $this->checkInfo($paypalprofile, 'name'),
            'email'  => $this->checkInfo($paypalprofile, 'email'),
            'avatar' => null,
        ];

        $normedprofile['pseudonym'] = $this->guestPseudonym($paypalprofile, ['given_name','family_name']);

        return ($normedprofile);
    }
}