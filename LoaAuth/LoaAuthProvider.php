<?php namespace OidConnect\LoaAuth;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 Service Provider to register LOA enable authentication service
 *          extend standard Laravel-5 Guard/Auth to support Level of Assurance.
 *
 * Reference: http://alexrussell.me.uk/laravel-cheat-sheet/
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


use Illuminate\Auth\AuthServiceProvider;

class LoaAuthProvider extends AuthServiceProvider {


	/**
	 * Overload class to force LoaAuthManager Usage
	 */
	protected function registerAuthenticator()	{

        $abstract = 'OidConnect\LoaAuth\LoaAuthContract';

		$this->app->bind ("auth", function($app) {
			// Once the authentication service has actually been requested by the developer
			// we will set a variable in the application indicating such. This helps us
			// know that we need to set any queued cookies in the after event later.
			$app['auth.loaded'] = true;
			return new LoaAuthManager($app);
		});

		$this->app->singleton('auth.driver', function($app)	{
			return $app['auth']->driver();
		});

		// need to enforce this to overload alias set in application.php
		$this->app->bind ($abstract, 'auth.driver');

	}
}
