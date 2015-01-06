<?php namespace OidConnect\UserManagement;

  // Facade Reference: http://fideloper.com/create-facade-laravel-4

use Illuminate\Support\Facades\Facade;

class UserProfileFacade extends Facade {

	protected static function getFacadeAccessor() { return 'OidConnect\UserManagement\UserProfileInterface';}

}
