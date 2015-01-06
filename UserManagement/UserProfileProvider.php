<?php namespace OidConnect\UserManagement;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 service provider to register SocialAuth Driver Manager
 *
 * Reference: http://alexrussell.me.uk/laravel-cheat-sheet/
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Illuminate\Support\ServiceProvider as SuperManagerClass;

class UserProfileProvider extends SuperManagerClass {

	protected $defer = true; // Defer execution util first call

	// Class's abstract Interface to register in $app[]
	protected $abstract = 'OidConnect\UserManagement\UserProfileInterface';

	public function register()	{

		// Register DriverManager Classe. In Bindshare mode class is instantiated once & shared with further call
		$this->app->bindShared(
			$this->abstract,                                         // abstract interface effective method
			function($app) {return new UserProfileService($app);}    // effective call to instantiate the object
		);

	}

	// In defer mode we have to register the event that forces instantiation.
	public function provides() 	{ return [$this->abstract];	}
}
