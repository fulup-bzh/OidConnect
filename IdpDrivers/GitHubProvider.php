<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : Github OAuth2 provider driver.
 * Note   : Github does not support even a simple form of OpenID-Connect federation.
 *          We shall request full userprofile to retrieve user's UID even if user was already federated.
 *
 * References:
 *   Dashboard:
 *   Documents: https://developer.github.com/v3/oauth/
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


// reference: https://developers.github.com/docs/github-login/manually-build-a-login-flow/v2.2
class GitHubProvider extends _DriverSuperClass {

	// main IDP configuration URLs
	protected $authTokenUrl  = 'https://github.com/login/oauth/authorize';
	protected $accessTokenUrl= 'https://github.com/login/oauth/access_token';
	protected $identityApiUrl= 'https://api.github.com/user';

    // OAuth2 action-1:  getAuthUrl($state) build authorization token url
	protected $scopes        = ['user,email'];

	// OAuth2 action-2: getAccessToken($code) request access token remove basic auth from header
	protected $headers = [
		'Content-type' => 'application/x-www-form-urlencoded',
		'Accept' => 'application/json',
	];

	// OAuth2 action-3: request User attributes from IDP Identiry Rest API
	// OAuth2 action-3: request User attributes through (Rest API)
	protected function getUserByToken($tokens)	{

		$authheader = [
			'Accept' => 'application/json',
			'Authorization' => 'Token ' . $tokens['access_token'],
		];

		// this a new user let's try IDP Identity APIs
		$response = $this->getHttpClient()->get($this->identityApiUrl , ['headers' => $authheader]);
		$body     = $response->getBody();
		$profile  = json_decode($body, true);

		// GitHub is not OpenId Connect compliant we get socialuid only with user profile
		$tokens['socialuid'] = $profile ['id'];
		$socialuser = $this->getUserByFedKey ($tokens);

		// normalize IDP profile for the application
		$socialuser->profile = $this->normalizeprofile ($profile);

		return ($socialuser);
	}


	// each IDP has its own profile schema, while application expects a standard one !!!
	protected function normalizeProfile ($githubprofile) 	{
		$normedprofile = [
  		   'loa'       => $this->loa,
			'name'      => $this->checkInfo($githubprofile, 'name'),
			'email'     => $this->checkInfo($githubprofile, 'email'),
			'avatar'    => $this->checkInfo($githubprofile, 'avatar_url'),
		];

		// Github as no pseudonym let's try to create an acceptable default
		$normedprofile['pseudonym'] = $this->guestPseudonym($githubprofile, ['login']);

		return ($normedprofile);
	}
}
