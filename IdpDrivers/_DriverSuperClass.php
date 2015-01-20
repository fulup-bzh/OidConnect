<?php namespace OidConnect\IdpDrivers;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : OpenId-Connect client implementation
 * Note   : For non openid-connect compliant providers. Users should shortcut openid
 * jsonwebtoken id_token parsing and force usage of what ever customer id-token is
 * provided by this IDP.
 *
 * Reference:
 *  Documents: http://openid.net/specs/openid-connect-basic-1_0-32.html#TokenOK
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Symfony\Component\HttpFoundation\RedirectResponse;

use Request;
use Session;
use App;

abstract class  _DriverSuperClass {

    protected  $openidconnect = false;  // we are 100% OpenID compliant
    protected  $idtokenname   = null;
    protected  $authheader    = null;
    protected  $oidsubject    = 'sub';

    // we need some extra parameters for OpenID Connect
    public function __construct ($app, $config, $fedKeyModel, $socialUser) {


        $this->app            = $app;
        $this->config         = $config;
        $this->name           = $config ['name'];
        $this->uid            = $config ['uid'];
        $this->loa            = $config ['loa'];
		$this->clientId       = $config ['client_id'];
		$this->redirectUrl    = $config ['redirect'];
		$this->clientSecret   = $config ['client_secret'];

        $this->fedKeyModel    = new $fedKeyModel;
        $this->socialUser     = $socialUser;

        // if auth is not statically defined than create one with basic auth
        if ($this->authheader == null) {
            $this->authheader = [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ":" . $this->clientSecret),
            ];
        }
    }

    public function info ($slot) {
        if (!array_key_exists ($slot, $this->config)) return null;
        return ($this->config[$slot]);
    }

    // OAuth2 action-1:  build authorization token url (GET method)
    protected function getAuthUrl($state)	{
        $query = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'response_type' => 'code',
            'state'         => $state,
        ];

        // only add scope and language when available
        if ($this->scopes != null) $query ['scope'] = implode(' ',$this->scopes);
        $query ['language'] = App::getLocale();

        $ret= $this->authTokenUrl . '?' . http_build_query($query);
        return $ret;
    }


    // OAuth2 action-2: request access token (POST method)
    public function getAccessToken($code)	{

        $body= [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectUrl,
            'code'          => $code,
        ];
        $post= [
            'headers'       => $this->authheader,
            'body'          => $body,
        ];

        // post request and retreive OpenID connect Tokens [with a S]
        $response = $this->getHttpClient()->post($this->accessTokenUrl,$post);
        $tokens   = json_decode($response->getBody(), true);
        if ($this->openidconnect) {
            /* let's extract OpenID json indentity token. JsobWebToken patern :
               - BASE64URL(UTF8(JWS Protected Header)) || '.' ||
               - BASE64URL(JWS Payload) || '.' ||
               - BASE64URL(JWS Signature)
            */
            // split token and unset token that we do not need anymore
            $jsonwebtoken  = explode(".", $tokens['id_token']);
            unset ($tokens['id_token']);

            // extract payload to an associative array ignore other data.
            // $OIDheader    = json_decode (base64_decode($jsonwebtoken [0]), true);
            $OIDpayload   = json_decode (base64_decode($jsonwebtoken [1]), true);
            //$OIDsignature = json_decode (base64_decode($jsonwebtoken [2]), true);

            // we need 'socialuid' for federation, other attributes might be useful to application
            $tokens ['socialuid'] = $OIDpayload [$this->oidsubject];
        } else {
            // For non OpenID IDPs let's retreive token from its name

            if ($this->idtokenname != null) {
                $tokens ['socialuid'] = $tokens [$this->idtokenname];
            }
        }

        return ($tokens);
    }

    // OAuth2 action-3: request User attributes through (Rest API)
    protected function getUserByToken($tokens)	{

        $authheader = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $tokens['access_token'],
        ];

        // OpenId-Connect compliant IDP let's check for federation
        $socialuser = $this->getUserByFedKey ($tokens);
        if ($socialuser->fedkey != 0)  return $socialuser;

        // if no Identity API specified return right after authentication
        if ($this->identityApiUrl == null) return ($socialuser);

        // this a new user let's try IDP Identity APIs
        $response = $this->getHttpClient()->get($this->identityApiUrl , ['headers' => $authheader]);
        $body     = $response->getBody();
        $profile  = json_decode($body, true);

        // merge IDPs user's profile information with previous authorisation tokens
        $socialuser->profile = $this->normalizeprofile ($profile);

        return ($socialuser);
    }


    // check if user is already known in our federation DB
    public function getUserByFedKey ($tokens) {

        if (array_key_exists ('socialuid',$tokens)) {
            // search for user in federation table
            $fedkey = $this->fedKeyModel->where('idp_uid', '=',$this->uid )->where('social_uid', '=', $tokens ['socialuid'])->first();

            if (count($fedkey) != 0) {
                $socialuser = new $this->socialUser ($this, $tokens, $fedkey->getAttributes());
                return ($socialuser);
            }
        }
        $socialuser = new $this->socialUser ($this, $tokens, null);
        return ($socialuser);
    }

    // update user federation link to map social uid and local user id
    public function  federate ($socialuser, $localuserid) {

        // user is unknown let's create a federation
        if ($socialuser->fedkey == null) {

           // check user is not sending twice the same consent page
           $fedkey = $this->fedKeyModel->where('idp_uid', '=',$this->uid )->where('social_uid', '=', $socialuser->tokens['socialuid'])->first();

           // user is definitely not federate
           if (count($fedkey) == 0) {
               $fedkeyrecord = new $this->fedKeyModel();
               $fedkeyrecord->idp_uid = $this->uid;
               $fedkeyrecord->social_loa = $this->loa;
               $fedkeyrecord->social_uid = $socialuser->tokens['socialuid'];
               $fedkeyrecord->user_id = $localuserid;
               $fedkeyrecord->save();
           }
        } else {
            // user is known update record if federation LOA changed
            $fedkeyrecord = $this->fedKeyModel->find ($socialuser->fedkey['id']);
            if ($fedkeyrecord->social_loa != $socialuser->fedkey['social_loa']) {
                $fedkeyrecord->social_loa =  $socialuser->fedkey['social_loa'];
                $fedkeyrecord->save();
            }
        }
    }

    // delete federation link from DB
    public function unfederate ($socialuser)	{

        $fedkeyrecord= $this->fedKeyModel->find($socialuser->fedkey['id']);
        $fedkeyrecord->delete();
    }


    // make shure every provider implement at least this method
    protected abstract function normalizeProfile ($idpprofile);

    // Helper to extract data from IDP JSON structure
    protected function checkInfo ($obj, $slot1, $slot2=null) {
        if (! array_key_exists ($slot1, $obj)){
            return (null);
        }
        if ($slot2 == null) {
            return ($obj [$slot1]);
        }
        if (! array_key_exists ($slot2, $obj[$slot1])){
            return (null);
        }
        return ($obj [$slot1][$slot2]);
    }

    // helper to provide a default pseudonym for social user
    protected function guestPseudonym (array $idpprofile, $slots) {
        $pseudo = '';
        foreach ($slots as $slot) {
            if (array_key_exists ($slot, $idpprofile)) {
                $pseudo .= strtr ($idpprofile [$slot],' ','-');
                if (strlen($pseudo) >= 8) {
                    break;
                }
                $pseudo .= '-';
            }
        }

        if (strlen($pseudo) >= 8) {
            $pseudo .=  '-' . rand(1000,9999);
        }
       return $pseudo;
    }

    /**
     * Note in OpenID connect OAuth phase-3 can be shortcut
     * if user was previously federated. For this reason
     * when users are known from our federation DB. Call
     * to getUserByToken will return directly, not calling
     * IDP Identity REST APIs. In this case $profile hold
     * directly a local userid and no provisioning data.
     */
    public function getIdpSocialUser($request)	{

        // check if user has a valid state when returning from IDP with its code
        if ($this->hasInvalidState($request)) throw new \InvalidArgumentException("getIdpSocialUser invalid state in IDP response.");

        // get access token from IDP [OAuth phase-2]
        $accesstokens = $this->getAccessToken($request->input('code'));

        // get user profile from access token from IDP [OAuth phase-3]
        $profile = $this->getUserByToken ($accesstokens);

        return ($profile);
    }

    /** set a random number in session and use it to certify of exchange with the IDP */
    public function getIdpAuthorization ()   {

        $state = sha1(time());
        Session::set ('state', $state);

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /** verify current session state fits with input request one */
    public function hasInvalidState($request) {
          $requeststate =  $request->input('state');
          $sessionstate =  Session::get ('state');

          return ! ($requeststate === $sessionstate);
    }

    protected function getHttpClient()  {
        return new \GuzzleHttp\Client;
    }
}