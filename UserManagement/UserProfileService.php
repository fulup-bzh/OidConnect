<?php namespace OidConnect\UserManagement;
/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : Implement a common set of basic Identity Management functions for Controllers
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */


use Config;
use OidConnect\DriverManager\IdpFactoryFacade as IdpFactory;
use OidConnect\Models\CheckCodeModel;
use Hash;
use Mail;
use Lang;

// Return a single instance of IDP config
class IdpInfo {
    public $uid;
    public $name;
    public $logo;
    public $auth;
    public $used;
    public $fedid;

    public function __construct($config) {

        $this->uid  = $config ['uid'];
        $this->name = $config ['name'];
        $this->logo = $config ['logo-img'];
        $this->auth = $config ['login-img'];
    }
}

class UserProfileService implements UserProfileInterface  {

    protected $idpindex = null;  // pointer to IDPs config

    public function __construct ($app) {

        $this->usermodelclass = $app['config']['OidConnect.dbusermodel'] ?: $app['config']['auth.model'] ;
        $this->fedkeyclass    = $app['config']['OidConnect.dbusermodel'] ?: 'OidConnect\Models\FedKeyModel' ;
        $this->usermodel   = new $this->usermodelclass ();
        $this->fedkeymodel = new $this->fedkeyclass();
    }

    // Merge list of supported IDP and user's federated ones
    public function getIdpInfo($fedkeys) {
        $idpinfos = [];

        // let's retrieve existing IDP configs
        if ($this->idpindex == null) $this->idpindex = IdpFactory::getConfigByUid() ;

        foreach ($this->idpindex as $idpuid  => $idpconfig) {
            $idpinfos [$idpuid] = new IdpInfo ($idpconfig);
        }

        // update user's list of used social idp and push to to view
        foreach ($fedkeys as $fedkey) {
            $idpinfo= $idpinfos  [$fedkey->idp_uid];
            $idpinfo->used  = true;
            $idpinfo->fedid = $fedkey->id;
        }

        return ($idpinfos);
    }


    // return full user profile from logged user or any one if super admin
	public function getUserProfile($auth, $requestid = 0)	{

        // get logged user ID
        $loggedid= $auth->id();

        // if not logged [guest] return an empty profile
        if ($loggedid == 0) return null;

        // If not a manager let's for user own id usage
        if (($loggedid != 1) || ($requestid == 0)) {
           $requestid =  $loggedid;
        }

        // let's get user and its federation keys
        $userinfo =$this->usermodel->find ($requestid);

        // user does not exist
        if ($userinfo == null) return null;

        $userkeys = $userinfo->fedkeys; // warning: no '()' at the end or it creates an empty 'and' condition !!!

        // merge the list of existing and used IDPs
        $idpinfos = $this->getIdpInfo($userkeys);

        // let's return user dashboard profile to application
        return ['userinfo' => $userinfo->getAttributes(), 'idpinfos' => $idpinfos];
    }

    public function getUserProfileIndex ($auth, $filter=null) {

        // Warning: null filter return the full user base
        if ($filter == null) $userindex = $this->usermodel->all();
        if ($userindex == null) return (null);

        $users = []; // build users list with associated keys
        foreach ($userindex as $userinfo) {

            $users [$userinfo->id] = [
               'userinfo' => $userinfo->getAttributes(),
               'fedkeys'  => $userinfo->fedkeys,
            ];
        }
        return $users;
    }

    public function updateUserProfile ($profile)   {

        $localuser= $this->usermodel->find($profile['id']);
        if ($localuser == null) return (null);

        // email is updated only after verification
        $localuser->pseudonym = $profile['pseudonym'];
        if ($profile['avatar'] != null) $localuser->avatar= $profile['avatar'];

        // if user change his email, don't change it but send a verificationmail
        if ($localuser->email != $profile ['email']) {
            $this->sendVerificationCode ($localuser, $profile ['email']);
            $localuser->email = $profile ['email'];
            $localuser->loa   = 0;
        }

        $localuser->save();

        return ($localuser);
    }

    public function provisionSocialUser ($socialuser, $email, $pseudonym, $loa)   {
        $profile= $socialuser->profile;
        $fedkey = $socialuser->fedkey;

        $localuser = new $this->usermodelclass ();

        $localuser->pseudonym =  strtr(trim($pseudonym), " ", "-");
        $localuser->email = trim($email);

        if ($profile['avatar'] != null) {
            $localuser->avatar = $profile['avatar'];
        } else {
            $localuser->avatar = $socialuser->provider->info('avatar-img');
        }

        $localuser->save();
        return $localuser;
    }

    // this entry point is used when a user create a local only account
    public function provisionBasicLocalUser ($auth, $profile, $newuser=false)   {

        $id = $auth->id();
        if ($id == 0 && !$newuser)  throw new \InvalidArgumentException("provisionBasicLocalUser user should be logged.");

        if (!$newuser) $localuser=  $this->getLocalUserById ($id);
        else {
            // create a new user, but lock the email until validation
            $localuser= new $this->usermodelclass ();
        }

        $localuser->pseudonym = strtr(trim($profile['pseudonym']), " ", "-");
        $localuser->avatar = $profile['avatar'];
        $localuser->password = Hash::make ($profile['password']);

        // if new email reset loa to untrusted until email is confirmed
        if ($localuser->email != $profile ['email']) {
            $localuser->loa = 0;
            $localuser->email = $profile['email'];
        }

        // update localuser in DB
        $localuser->save();

        // make sure localuser is updated before sending confirmation code
        if ($localuser->loa == 0) $this->sendVerificationCode ($localuser, $profile ['email']);

        return $localuser;
    }

    // this entry point is used when a user create a local only account
    public function passwdUpdateLocalUser ($auth, $password)  {
        $id = $auth->id();
        if ($id == 0)  throw new \InvalidArgumentException("passwdUpdateLocalUser user should be logged.");
        $localuser=  $this->getLocalUserById ($id);
        $localuser->password = Hash::make ($password);
        $localuser->save();

        return $localuser;
    }

    // if user with this email does not exist create it now
    public function findOrProvisionSocialUser ($socialuser, $email, $pseudonym, $loa=0)   {

        if ($email == null) $email= $socialuser->profile['email'];

        // search user by email [using pseudonym would impose to recheck email]
        $localuser =  $this->findLocalUserByPseudonym($pseudonym);
        if ($localuser != null) return null;

        // search user by email [using pseudonym would impose to recheck email]
        $localuser =  $this->findLocalUserByEmail($email);

        // user does not exist let's provision it from IDP profile info
        if (count($localuser) == 0) {
            $localuser = $this->provisionSocialUser ($socialuser, $email, $pseudonym, $loa);
        }
        return ($localuser);
    }

    public function findLocalUserByEmail ($email) {
        return $this->usermodel->whereEmail($email)->first();
    }

    public function findLocalUserByPseudonym ($pseudonym) {
        return $this->usermodel->whereEmail($pseudonym)->first();
    }

    public function findLocalUserByLogin ($login)  {
        return  $this->usermodel->where('email', '=', $login)->orWhere('pseudonym', '=', $login)->first();
    }

    public function newLocalUser ()  {
        return  new $this->usermodelclass ();
    }

    public function getLocalUserById ($userid) {
        $localuser= $this->usermodel->find($userid);
        if ($localuser == null) return (null);

        return ($localuser);
    }

    // return fedkeys attached to a given user
    public function getLocalUserFedKeys ($localuser) {
        // get logged user ID
        $fedkeys = $this->fedkeymodel->whereUserId ($localuser->id)->get();

        return ($fedkeys);
    }

    public function deleteLocalUserById ($auth , $userid)	{
        // prevent anyone from delete superadmin
        if ($userid <= 1) {
             return true;
        }

        // normal user can only delete selfdelete
        $loggedid = $auth->id();
        if ($loggedid != 1 && ($loggedid != $userid)) {
            throw new \InvalidArgumentException("UserProfileService [".$loggedid ."] not owner of fedkey.");
        }

        $localuser= $this->usermodel->find($userid);
        if ($localuser == null) return (null);

        $deluser = $localuser->getAttributes();
        $localuser->delete();
        return ($deluser);
	}

    public function getFedKey ($fedid) {
        $fedkey= $this->fedkeymodel->find($fedid);
        if ($fedkey == null) return (null);

        $delfedkey = $fedkey->getAttributes();
        return ($delfedkey);
    }

    public function deleteFedKey ($auth, $fedid)	{

        // if valid get fedkey from DB
        $fedkey= $this->fedkeymodel->find($fedid);
        if ($fedkey == null) return (null);

        // check fedkey is own by logged user
        $loggedid = $auth->id();
        if ($loggedid != 1 && ($loggedid != $fedkey->user_id)) {
            throw new \InvalidArgumentException("UserProfileService [".$loggedid ."] not owner of fedkey.");
        }

        $delfedkey = $fedkey->getAttributes();
        $fedkey->delete();
        return ($delfedkey);
   	}

    // send a verification mail, when mail is validated the LOA is updated
    // if IDP==0 localuser LOA is updated if not IDP corresponding key is updated
    public function sendVerificationCode ($localuser, $email) {

        // Standard case we send confirmation email with a confirmation code [we try to keep the same code for full session]
        $randomcode = strtoupper(substr(md5(rand()), 0, 2) . "-" . rand(100000, 999999));

        Mail::send('emails.login.check-code-'. \App::getLocale(), array('pseudonym' => $localuser->pseudonym, 'checkcode' => $randomcode, 'email' => $email)
            , function ($message) use ($localuser, $email) {
                $message->to($email, $localuser->pseudonym)->subject( Lang::get ('profile.email-check-code', ['USER' => $localuser->pseudonym]));
            });

        // if a valide for this email already exist drop it
        $verification= CheckCodeModel::whereEmail ($email)->first();
        if ($verification != null)  $verification->delete();

        // if user already has a validation code replace it with a new one
        $verification= CheckCodeModel::whereUserId ($localuser->id)->first();
        if ($verification == null)  $verification= new CheckCodeModel ();

        $verification->email    = trim (strtolower($email));
        $verification->code     = strtoupper($randomcode);
        $verification->user_id  = $localuser->id;
        $verification->save();
    }

    public function findLocalUserByVerificationCode ($token) {
        $code =  trim (strtoupper($token));
        $verification= CheckCodeModel::whereCode ($code)->first();
        if ($verification == null) return (null);

        return $this->getLocalUserById ($verification->user_id);
    }

    public function updateEmailByVerificationCode ($token) {

        // check we have ca code
        $code =  trim (strtoupper($token));
        $verification= CheckCodeModel::whereCode ($code)->first();
        if ($verification == null) return (null);

        // find user attached to this code
        $localuser = $this->getLocalUserById ($verification->user_id);
        $localuser->email = $verification->email;

        // update LOA if needed
        if ($localuser->loa == 0 )   $localuser->loa=1;

        // user save will fail if email is already used
        try {
            $localuser->save();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("UserProviderService Email:". $localuser->email ." already exist.");
        }

        // everything is ok, let's delete verification token
        $verification->delete();
        return ($localuser);
    }

}
