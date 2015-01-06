<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : Facebook OAuth2 provider driver.
 * Note   : FaceBook does not support even a simple form of OpenID-Connect federation.
 *          We shall request full userprofile to retrieve user's UID even if user was already federated.
 *
 * References:
 *   Dashboard: https://developers.facebook.com/apps
 *   Documents: https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/v2.2
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


// reference: https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/v2.2
class FacebookProvider extends _DriverSuperClass {

	// main IDP configuration URLs
	protected $authTokenUrl  = 'https://www.facebook.com/dialog/oauth';
	protected $accessTokenUrl= 'https://graph.facebook.com/oauth/access_token';
	protected $identityApiUrl= 'https://graph.facebook.com/me';
	protected $scopes        = ['email'];

	// OAuth2 action-1:  build authorization token url
	// use parent class for getAuthUrl($state)

	// OAuth2 action-2: request access token (Get method for Facebook !!!) remove basic auth from header
	public function getAccessToken($code)	{

		$query=[
			'client_id'     => $this->clientId,
			'redirect_uri'  => $this->redirectUrl,
			'client_secret' => $this->clientSecret,
			'code'          => $code,
		];

		// Will raise a bad response exception if token timeout [common error when source debugging]
		$response = $this->getHttpClient()->get($this->accessTokenUrl ,['query' => $query]);
		$body= $response->getBody();

		// WARNING Facebook does not use json but a string containing $access_token
		// access_token={access-token}&expires={seconds-til-expiration}
		parse_str($body);  // huggly method that parse body string and create PHP variables.
        $tokens = ['access_token' => $access_token, 'expires' => $expires];

		return ($tokens);
	}

	// OAuth2 action-3: request User attributes from IDP Identiry Rest API
	protected function getUserByToken($tokens)	{

		$authheader = [
			'Accept' => 'application/json',
			'Authorization' => 'Bearer ' . $tokens['access_token'],
		];

		// call IDP's Identity APIs to retreive user profile
		$response = $this->getHttpClient()->get($this->identityApiUrl , ['headers' => $authheader]);
		$profile  = json_decode($response->getBody(), true);

        // Facebook is not OpenId Connect compliant we get socialuid only with user profile
		$tokens['socialuid'] = $profile ['id'];
		$socialuser = $this->getUserByFedKey ($tokens);

		// normalize IDP profile for the application
		$socialuser->profile = $this->normalizeprofile ($profile);

		return ($socialuser);
	}

	// each IDP has its own profile schema, while application expects a standard one !!!
	protected function normalizeProfile ($facebookprofile) 	{
		$normedprofile = [
			'loa'       => $this->loa,
			'name'      => $this->checkInfo($facebookprofile, 'first_name'),
			'email'     => $this->checkInfo($facebookprofile, 'email'),
			'avatar'    => 'https://graph.facebook.com/'.$facebookprofile['id'].'/picture?',
		];

		// if we have a family name add it to our pseudonym
		$familyName = $this->checkInfo($facebookprofile,'last_name');
		if ($familyName != null) {
			$normedprofile['name']  .= '-' .$familyName;
		}

		// Facebook as no pseudonym let's try to create an acceptable default
		$normedprofile['pseudonym'] = $this->guestPseudonym($facebookprofile, ['first_name', 'last_name']);

		return ($normedprofile);
	}
}
