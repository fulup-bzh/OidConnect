<?php namespace OidConnect\UserManagement;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 service provider to register SocialAuth Driver Manager
 *
 * When provider is called from IOC by the application, it must call
 * driver method to retreive a new instance of Sauth2/Provider/IdpName
 *
 * Reference: http://alexrussell.me.uk/laravel-cheat-sheet/
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

interface UserProfileInterface {

    public function getIdpInfo($fedkeys);
}