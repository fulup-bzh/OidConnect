<?php namespace OidConnect\DriverManager;

// http://fideloper.com/create-facade-laravel-4

use Illuminate\Support\Facades\Facade;

class IdpFactoryFacade extends Facade {

	/**
	 *
	 */
	protected static function getFacadeAccessor() { return 'OidConnect\DriverManager\IdpFactoryInterface';}

}
