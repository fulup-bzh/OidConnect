<?php namespace OidConnect\DriverManager;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : Parse app/config/services files and create corresponding IDPs drivers
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Config;

use Illuminate\Support\Manager;

class IdpFactoryManager extends Manager implements IdpFactoryInterface {

	public $driverindex=null;
	public $uidconfigindex=null;

	public function __construct($app) {
		parent::__construct($app);

		if (! $app['config']['OidConnect']) throw new \InvalidArgumentException("OidConnect config file [app/config/OidConnect.php] not found.");

		$this->fedKeyModel = $app['config']['OidConnect.dbfedkeymodel'] ?: 'OidConnect\Models\FedKeyModel';
		$this->socialUser  = $app['config']['OidConnect.socialuser']    ?: 'OidConnect\UserManagement\SocialUser';

	} // end constructor

	// this function will be called for any every fake Make-method
	protected function createDriver($driver) {

	   $idpconfig = $this->app['config']['OidConnect.'.$driver];
       if ($idpconfig == null) throw new \InvalidArgumentException("OidConnect driver [".$driver."] not found in [app/config/OidConnect].");

		// Add few useful things to IDP config
		$idpconfig['driver'] = $driver;

       // build IDP driver
       $idp = new $idpconfig['provider'] ($this->app, $idpconfig, $this->fedKeyModel, $this->socialUser);

	   // store IDP provider object in index
	   $this->driverindex [$driver]   = $idp;
	   return 	$idp;
	}

	// return an IDP object instance from its index name
	public function getIdpByDriverIndex ($driver) {
		if (!array_key_exists ($driver, $this->driverindex)) return null;
		return $this->driverindex [$driver];
	}

	// return sorted by UID index
	public function getConfigByUid ($uid=null) {

		// let's build IDP index at 1st call
		if ($this->uidconfigindex == null) {
			$idpconfigs = Config::get ("OidConnect");
			if ($idpconfigs == null) {
				throw new \InvalidArgumentException("OidConnect config file [app/config/OidConnect.php] not found.");
			}

			foreach ($idpconfigs as $drivername => $idpconfig) {
				if (gettype ($idpconfig) == "array" ) {
					$idpconfig ['driver'] = $drivername;
					$this->uidconfigindex[$idpconfig ['uid']] = $idpconfig;
				}
			}
		}

		if ($uid == null) return ( $this->uidconfigindex);
		if (!array_key_exists ($uid, $this->uidconfigindex)) return null;
		return ($this->uidconfigindex[$uid]);
	}

	// when OpenId Connect will be stable enough we may have a generic default driver that works with most IDPs :)
	public function getDefaultDriver() {
		throw new \InvalidArgumentException("OidConnect No default driver.");
	}
}