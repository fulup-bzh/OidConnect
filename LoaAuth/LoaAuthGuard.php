<?php namespace OidConnect\LoaAuth;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 Service Provider to register LOA enable authentication service
 *          extend standard Laravel-5 Guard/Auth to support Level of Assurance.
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Guard;

class LoaAuthGuard extends Guard  implements LoaAuthContract {

	public    $loalevel=null;
	public    $loaname=null;

	// create a permanent name to store LOA in session
	public function getLoaName() {
       if (is_null($this->loaname)) {
		   $this->loaname = 'loa_' . md5(get_class($this));
	   }
	   return 	$this->loaname;
	}

	// if we have LOA in cache return it, otherwise retreive it from session
	public function loa ($requestedLoa = 0) {

		// object does not need to be restored from session
		if (is_null($this->loalevel)) {

			// keep track of LOA in case we get more than one request
			$this->loalevel = $this->session->get($this->getLoaName());
		}

		// not logged force LOA to zero
		if (!$this->check()) {
			$this->loalevel = 0;
		}

		// if requestedLoa is provided return true of false
		if ($requestedLoa != 0) {
		   if ($requestedLoa > $this->loalevel) return 'fx';
		   else return 'ok';
		}

		// no requested LOA return current loa
		return $this->loalevel;
	}

	// helper to store in session target url & loa
	public function intended ($uri, $loa) {
		$this->session->set ('url.intended',$uri);
		$this->session->set ('loa.intended',$loa);
	}

	// send user password for validation to auth->provider
	public function validatePassword ($locauser, $password) {
		return $this->provider->validateCredentials ($locauser, ['password' => $password]);
	}

	// user can user indifferently is email or pseudo to log in
	public function logByPseudoOrEmail ($username, $password, $remember = false) {

		$this->fireAttemptEvent(['username'=> $username, 'password' => $password], $remember, false);

		// 1st try with pseudonym of what ever key was passed
		$user = $this->provider->retrieveByCredentials(['pseudonym'=> $username]);

		// if 1st try fail replace pseudonym with email
		if ($user == null) {
			$user = $this->provider->retrieveByCredentials(['email'=> $username]);
		}

		// if user does not exist return now
		if ($user == null) return null;

        // not sure when this is used but parent class does it ???
		$this->lastAttempted = $user;

		// send back user to Eloquent Auth Provider to check password
		if ($this->provider->validateCredentials($user, ['password' => $password])) {
			 $this->loginWithLoa ($user, $user->loa, $remember);
			 return $user;
		};

        return null;
	}

	public function loginWithLoa(Authenticatable $user, $loa, $remember = false) {

		// call parent login class and build user if needed
		$this->login ($user,  $remember);

		// keep track of LOA in both current object and session
		$this->loalevel = $loa;
		$this->session->set ($this->getLoaName(), $this->loalevel);
	}

	public function logout () {
		$this->loalevel = 0;
		$this->session->set ($this->getLoaName(), $this->loalevel);
		parent::logout();
	}
}
