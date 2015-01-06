<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : Microsoft OpenID-Connect OAth2 provider.
 * Note   :
 *
 * Reference:
 *  Dashboard: https://account.live.com/developers/applications
 *  Documents: http://msdn.microsoft.com/en-us/library/hh243647.aspx
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Illuminate\Http\Request;

class MicrosoftProvider extends _DriverSuperClass {

	// main IDP configuration URLs
	protected $openidconnect = false;  // As today Microsoft complies with  OAuth2 but not with OpenID-Connect
	protected $authTokenUrl  = 'https://login.live.com/oauth20_authorize.srf';
	protected $accessTokenUrl= 'https://login.live.com/oauth20_token.srf';
	protected $identityApiUrl= 'https://apis.live.net/v5.0/me';


	// OAuth2 action-1:  getAuthUrl($state) build authorization token url
	protected $scopes = ['wl.signin','wl.emails'];  // request authentication and user's email

	// OAuth2 action-2: getAccessToken($code) request access token remove basic auth from header
	protected $headers = ['Content-type' => 'application/x-www-form-urlencoded'];

	// OAuth2 action-3: getUserByToken($tokens) request User attributes through (Rest API)
	protected $idtokenname= 'user_id'; // idtoken name return with OAuth2 access token

	// each IDP has its own profile schema, while application expects a standard one !!!
	protected function normalizeProfile ($microsoftprofile) 	{
		$normedprofile = [
  		    'loa'       => $this->loa,
			'name'      => $this->checkInfo($microsoftprofile, 'name'),
			'email'     => $this->checkInfo($microsoftprofile, 'emails','preferred'),
			'avatar'    => 'https://apis.live.net/v5.0/' .$microsoftprofile['id'] . '/picture',
		];

		// microsoft as no pseudonym let's try to create an acceptable default
		$normedprofile['pseudonym'] = $this->guestPseudonym($microsoftprofile, ['first_name', 'last_name']);

		return ($normedprofile);
	}

}
