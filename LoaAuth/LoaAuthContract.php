<?php namespace OidConnect\LoaAuth;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 Service Provider to register LOA enable authentication service
 *          extend standard Laravel-5 Guard/Auth to support Level of Assurance.
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Auth\Guard;

interface LoaAuthContract extends Guard {

    public function loa($requestedLoa =0);
    public function intended($url, $loa);
    public function loginWithLoa(UserContract $user, $loa, $remember = false);
}