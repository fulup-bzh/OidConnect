<?php namespace OidConnect\IdpDrivers;


/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : Orange OpenID-Connect OAth2 provider.
 * Note   : Orange is 100% compliant with OpenID connect and this provider
 *          should be used as reference for any other OpenID connect provider.
 *          Unfortunately validating authorization to access Orange APIs is a painful
 *          process with discard Orange as a candidate of chose for development and test.
 *
 * Reference:
 *  Dashboard: https://console.developers.google.com/project
 *  Documents: https://developers.google.com/accounts/docs/OpenIDConnect
 *             https://developers.google.com/+/api/openidconnect/getOpenIdConnect
 *  Discovery: https://accounts.google.com/.well-known/openid-configuration
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


class GoogleProvider extends _DriverSuperClass {

	// main IDP configuration URLs
	protected $openidconnect = true;  // Google support OpenID-Connect
	protected $authTokenUrl  = 'https://accounts.google.com/o/oauth2/auth';
	protected $accessTokenUrl= 'https://www.googleapis.com/oauth2/v3/token';
	protected $identityApiUrl= 'https://www.googleapis.com/plus/v1/people/me/openIdConnect';


	// OAuth2 action-1:  getAuthUrl($state) build authorization token url
	protected $scopes = ['openid','email','profile'];  // request authentication & email

	// OAuth2 action-2: getAccessToken($code) request access token remove basic auth from header
	protected $headers = ['Content-type' => 'application/x-www-form-urlencoded'];

	// OAuth2 action-3: getUserByToken($tokens) request User attributes through (Rest API)
	protected $idtokenname= 'user_id'; // idtoken name return with OAuth2 access token

	// each IDP has its own profile schema, while application expects a standard one !!!
	protected function normalizeProfile ($googleprofile) 	{
		$normedprofile = [
			'loa'       => $this->loa,
			'name'      => $this->checkInfo($googleprofile, 'name'),
			'email'     => $this->checkInfo($googleprofile, 'email'),
			'avatar'    => $this->checkInfo($googleprofile, 'picture'),
		];

		// google as no pseudonym let's try to create an acceptable default
		$normedprofile['pseudonym'] = $this->guestPseudonym($googleprofile, ['given_name', 'family_name']);

		return ($normedprofile);
	}

}
