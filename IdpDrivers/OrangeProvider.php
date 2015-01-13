<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : Orange OpenID-Connect OAth2 provider.
 * Note   : Orange is 100% compliant with OpenID connect and this provider
 *          is  the parent class reference for every other OpenID connect provider.
 *          Unfortunately validating authorization to access Orange APIs is a painful
 *          process with discard Orange as a candidate of chose for development and test.
 *
 * Reference:
 *  Dashboard: https://www.orangepartner.com/content/welcome-to-your-dashboard
 *  Documents: https://www.orangepartner.com/content/getting-started-user-details
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


class OrangeProvider extends _DriverSuperClass {

    // main IDP configuration URLs
    protected $openidconnect = true; // Orange is 100% OpenID compliant
    protected $authTokenUrl  = 'https://api.orange.com/oauth/v2/authorize';
    protected $accessTokenUrl= 'https://api.orange.com/oauth/v2/token';
    protected $identityApiUrl= 'https://api.orange.com/openidconnect/v1/userinfo';


    // OAuth2 action-1:  getAuthUrl($state) build authorization token url
    protected $scopes      = ['openid','profile'];

    // OAuth2 action-2: getAccessToken($code) request access token remove basic auth from header
    // use default basic auth header from parent class

    // OAuth2 action-3: getUserByToken($tokens) request User attributes from IDP's Rest API
    protected $uidtoken     = 'id_token'; // slot name of user's uid within OpenID-Connect tokens

    protected function normalizeprofile ($orangeprofile) 	{

        // Orange profile is quite basic !!!
        $normedprofile = [
            'loa'       => $this->loa,
            'name'   => $this->checkInfo($orangeprofile, 'name'),
            'email'  => null,
            'avatar' => null,
        ];

        $normedprofile['pseudonym'] = $this->guestPseudonym($orangeprofile, ['name']);

        return ($normedprofile);
    }
}