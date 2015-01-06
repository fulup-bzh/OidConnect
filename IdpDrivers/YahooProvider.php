<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : Yahoo OpenID-Connect OAth2 provider.
 * Note   : Yahoo is 95% compliant with OpenID connect.
 *          its return a user subject UID with authorization token which
 *          allow to check existence of federation before requesting full user profile.
 *          Nevertheless URI are somehow specifics and user consent is ask at each user login
 *          which make its usage somehow less smooth than other Social IDPs.
 *
 * Reference:
 *  Dashboard: https://developer.apps.yahoo.com/projects
 *  Documents: https://developer.yahoo.com/oauth2/guide/
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Session;

// references:
//     docs:      http://msdn.yahoo.com/en-us/library/hh243647.aspx
//     dashboard: https://account.live.com/developers/applications
//
class YahooProvider extends _DriverSuperClass {

	// main IDP configuration URLs
	protected $authTokenUrl  = 'https://api.login.yahoo.com/oauth2/request_auth';
	protected $accessTokenUrl= 'https://api.login.yahoo.com/oauth2/get_token';
	protected $identityApiUrl= 'https://social.yahooapis.com/v1/user';


	// OAuth2 action-1:  getAuthUrl($state) build authorization token url
	protected $scopes      = null;  // not used by yahoo

	// OAuth2 action-2: getAccessToken($code) request access token remove basic auth from header
	protected $idtokenname= 'xoauth_yahoo_guid'; // idtoken name return with OAuth2 access token

	// OAuth2 action-3: request User attributes through from IDP's Rest API
	protected function getUserByToken($tokens)	{

		// OpenId-Connect compliant IDP let's check for federation
		$socialuser = $this->getUserByFedKey ($tokens);
		if ($socialuser->fedkey != 0)  return $socialuser;

		// call IDP's Identity APIs to retreive user profile
		$query      = ['format'=>'json'];
		$authheader = [
			'Accept' => 'application/json',
			'Authorization' => 'Bearer ' . $tokens['access_token'],
		];
		$response = $this->getHttpClient()->get($this->identityApiUrl . '/' . $tokens['xoauth_yahoo_guid'] .'/profile', ['query' => $query, 'headers' => $authheader]);
		$body     = $response->getBody();
		$profile  = json_decode($body, true);

		// merge IDPs user's profile information with previous authorisation tokens
		$socialuser->profile = $this->normalizeprofile ($profile['profile']); // warning Yahoo as a sub array named 'profile' in profile

		return ($socialuser);
	}

    // each IDP has its own profile schema, while application expects a standard one !!!
    protected function normalizeProfile ($yahooprofile) 	{
		$normedprofile = [
   		   'loa'       => $this->loa,
		   'pseudonym' => $this->checkInfo($yahooprofile, 'nickname'),
		   'email'     => $this->checkInfo($yahooprofile, 'emails','primary'), // warning no email with yahoo
		   'avatar'    => $this->checkInfo($yahooprofile, 'image', 'imageUrl'),
    	];
		// if we have a family name add it to our pseudonym
		$normedprofile['pseudonym'] = $this->guestPseudonym($yahooprofile, ['nickname', 'familyName']);

		return ($normedprofile);
	}

}
