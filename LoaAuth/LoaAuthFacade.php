<?php namespace OidConnect\LoaAuth;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 Service Provider to register LOA enable authentication service
 *          extend standard Laravel-5 Guard/Auth to support Level of Assurance.
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Illuminate\Support\Facades\Facade;


class LoaAuthFacade extends Facade {

	protected static function getFacadeAccessor() { return 'auth.driver';}

}
