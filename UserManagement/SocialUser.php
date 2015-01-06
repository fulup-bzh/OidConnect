<?php namespace OidConnect\UserManagement;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: Laravel5/OidConnect
 * Object : SocialUser returned IDP authentication profile to the application.
 *          The class is design to be serialize and store in session context.
 *          It uses a facade to restore provider Driver handler from IoC.
 *
 * Note:   SocialUser can return both federated and unfederated users. At
 *         1st visit enduser is not federated and SocialUser will hold UserProfile
 *         as returned from the IDP, with no fedkeys. At second visit, SocialUser holds
 *         a LocalID pointing on users Local DataBase. SocialUser also holds some helpers.
 *         A pointer to the IDP driver, OAuthTokens in case the application would
 *         like to request more OAuth IDP's API and drivername for object restauration
 *         after serialisation.
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use OidConnect\DriverManager\IdpFactoryFacade as IdpFactory;
use OidConnect\UserManagement\UserProfileFacade as UserProfile;

/**
 * @property  driver
 */
class SocialUser {

	public    $profile=null;
	protected $driver;

    public function __construct ($provider, $tokens, $fedkey) {

		// user already logged and federated in the past he his known in our local user DB
        $this->provider    = $provider;             // authority that grant authentication
		$this->tokens      = $tokens;               // provider token might be needed for further API request
		$this->fedkey      = $fedkey;               // if user is known in local federation DB

		$this->driver=$provider->info('driver'); // need to restore provider after serialization
	}

	public function getUserByFedKey () {
      if ($this->fedkey == null ) return null;
	  return UserProfile::getLocalUserById ($this->getLocalUserByIdId());
	  return true;
    }

	public function federate ($localuser) {
	  $this->provider->federate ($this, $localuser->id);
    }

	public function unfederate () {
	  $this->provider->unfederate ();
    }

	// attributes match with Eloquent Fedkey table definition
	public function getLocalUserId () {
	  return $this->fedkey['user_id'];
    }

	public function getSocialUid () {
	  return $this->fedkey['social_uid'];
    }

	public function getSocialLoa () {
	  return $this->fedkey['social_loa'];
    }

	public function setSocialLoa ($loa) {
	   $this->fedkey['social_loa'] = $loa;
   	   $this->provider->federate ($this->fedkey , $this->fedkey->user_id);
    }

	// save IDP driver name before serializing
	public function __sleep() {
		return ['driver','tokens','fedkey','profile'];
	}

	// restore IDP object instance at unserializing
	public function __wakeup() {
		$this->provider = IdpFactory::getIdpByDriverIndex ($this->driver);
	}

}
