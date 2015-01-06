<?php namespace OidConnect\LoaAuth;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 Service Provider to register LOA enable authentication service
 *          extend standard Laravel-5 Guard/Auth to support Level of Assurance.
 *          L5 Guard is build with driver attach to app/config/auth/driver which
 *          by default is Eloquent. As a result by default method called when
 *          mapping a LoaAuthContract will be createEloquentDriver which should
 *          then return a LoaAuthGuard object.
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Illuminate\Auth\AuthManager;

class LoaAuthManager extends AuthManager {


    public function __construct($app)  {
        parent::__construct ($app);
        $this->loaGuardClass = $app['config']['OidConnect.authGuardModel'] ?:  'OidConnect\LoaAuth\LoaAuthGuard';
    }

    protected function callCustomCreator($driver)  {
        $custom = parent::callCustomCreator($driver);
        if ($custom instanceof Guard) return $custom;

        return new $this->loaGuardClass($custom, $this->app['session.store']);
    }

    public function createEloquentDriver()	{
		$provider = $this->createEloquentProvider();
		return new $this->loaGuardClass($provider, $this->app['session.store']);
	}


    public function createDatabaseDriver()   {
        $provider = $this->createDatabaseProvider();
        return new $this->loaGuardClass($provider, $this->app['session.store']);
    }

}