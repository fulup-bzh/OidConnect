<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : LinkedIn OpenID-Connect OAth2 provider.
 * Note   : Linked is not OpenID connect compliant. It does not even return a UID with the Authorisation token.
 *          The only usable IUD is returned when requesting basic profile. As a result first authentication
 *          uses 5 Oauth call and further authentication only 3.
 *          Federation is build on top profile LinkedIn ID extracted from siteStandardProfileRequest url.
 *
 * Reference:
 *  Dashboard: https://www.linkedin.com/secure/developer
 *  Documents: https://developer.linkedin.com/documents/authentication
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Session;

// references:
//     docs:      https://developer.linkedin.com/documents/authentication
//     dashboard: https://www.linkedin.com/secure/developer
//
class LinkedInProvider extends _DriverSuperClass {

	// main IDP configuration URLs
	protected $authTokenUrl  = 'https://www.linkedin.com/uas/oauth2/authorization';
	protected $accessTokenUrl= 'https://www.linkedin.com/uas/oauth2/accessToken';
	protected $identityApiUrl= 'https://api.linkedin.com/v1/people/~';


	// OAuth2 action-1:  getAuthUrl($state) build authorization token url
	protected $scopes      = ['r_basicprofile','r_emailaddress'];

	// OAuth2 action-1:  build authorization token url
	// use parent class for getAuthUrl($state)

	// OAuth2 action-2: request access token [Linked is not OpenIdConnect compliant but return a json access_token]


	// OAuth2 action-3: request User attributes from IDP Identiry Rest API
	protected function getUserByToken($tokens)	{

		$authheader = [
			'Accept' => 'application/json',
		];
		$query = [
			'oauth2_access_token' => $tokens['access_token'],
			'format' => 'json'
		];

		// call IDP's Identity APIs to retreive user profile
		$response = $this->getHttpClient()->get($this->identityApiUrl , ['headers' => $authheader, 'query' => $query]);
		$profile  = json_decode($response->getBody(), true);

		// extract profile id from 'siteStandardProfileRequest' and check for federation
		$lkinurl= $profile['siteStandardProfileRequest']['url'];
        $lkinquery=parse_url($lkinurl,PHP_URL_QUERY);
        parse_str($lkinquery,$lkinuser);
		$profile ['id'] = $lkinuser['id'];

		// LinkedIn is not OpenId Connect compliant we get socialuid only from basic user profile
		$tokens['socialuid'] = $profile ['id'];
		$socialuser = $this->getUserByFedKey ($tokens);
		if ($socialuser->fedkey != 0)  return $socialuser;

		// if user is unknown from federation table let's request its email profile
		$response = $this->getHttpClient()->get($this->identityApiUrl . "/email-address", ['headers' => $authheader, 'query' => $query]);
		$email  = json_decode($response->getBody(), true);
		$profile['email']= $email;

		// retreive user avatar url [note this could be a big image and should probably be realized]
		$response = $this->getHttpClient()->get($this->identityApiUrl .  "/picture-urls::(original)", ['headers' => $authheader, 'query' => $query]);
		$avatar  = json_decode($response->getBody(), true);
		$profile['avatar']= $avatar['values'][0];

		// normalize IDP profile for the application
		$socialuser->profile = $this->normalizeprofile ($profile);

		return ($socialuser);
	}

	// each IDP has its own profile schema, while application expects a standard one !!!
	protected function normalizeProfile ($linkedinprofile) 	{
		$normedprofile = [
			'loa'       => $this->loa,
			'name'      => $this->checkInfo($linkedinprofile, 'firstName'),
			'email'     => $this->checkInfo($linkedinprofile, 'email'),
			'avatar'    => $this->checkInfo($linkedinprofile, 'avatar'),
		];

		// if we have a family name add it to our pseudonym
		$familyName = $this->checkInfo($linkedinprofile,'lastName');
		if ($familyName != null) {
			$normedprofile['name']  .= '-' .$familyName;
		}

		// Facebook as no pseudonym let's try to create an acceptable default
		$normedprofile['pseudonym'] = $this->guestPseudonym($linkedinprofile, ['firstName', 'lastName']);

		return ($normedprofile);
	}
}
