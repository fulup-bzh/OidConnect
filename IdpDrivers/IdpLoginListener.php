<?php namespace OidConnect\IdpDrivers;

interface IdpLoginListener {

    /**
     * @param $user
     * @return mixed
     */
    public function userHasLoggedIn($user, $loa);

}
