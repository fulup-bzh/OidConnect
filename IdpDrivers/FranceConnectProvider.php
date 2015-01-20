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
 *  Dashboard: Not Available
 *  Documents: http://doc.integ01.dev-franceconnect.fr/
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


class FranceConnectProvider extends _DriverSuperClass {

    // main IDP configuration URLs
    protected $openidconnect = true; // Orange is 100% OpenID compliant
    protected $authTokenUrl  = 'http://fcp.integ01.dev-franceconnect.fr/user/authorize';
    protected $accessTokenUrl= 'http://fcp.integ01.dev-franceconnect.fr/user/token';
    protected $identityApiUrl= 'http://fcp.integ01.dev-franceconnect.fr/api/user';


    // OAuth2 action-1:  getAuthUrl($state) build authorization token url
    protected $scopes      = ['openid','profile','email'];

    // OAuth2 action-2: getAccessToken($code) request access token remove basic auth from header
    // use default basic auth header from parent class

    // OAuth2 action-3: getUserByToken($tokens) request User attributes from IDP's Rest API

    protected function normalizeprofile ($frconnectprofile) 	{

        // FranceConnect profile is quite basic !!!
        $normedprofile = [
            'loa'    => $this->loa,
            'name'   => $this->checkInfo($frconnectprofile, 'given_name'),
            'email'  => $this->checkInfo($frconnectprofile, 'email'),
            'avatar' => null,
        ];

        $normedprofile['pseudonym'] = $this->guestPseudonym($frconnectprofile, ['given_name', 'family_name']);

        return ($normedprofile);
    }
}