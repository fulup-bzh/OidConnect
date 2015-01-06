<?php namespace App\Http\Controllers;

use Illuminate\Http\Request as HttpRequest;

use OidConnect\LoaAuth\LoaAuthContract as LoaGuard;
use OidConnect\DriverManager\IdpFactoryInterface as IdpFactory;
use OidConnect\UserManagement\UserProfileFacade as UserProfile;
use Session;

/**
 * @Controller(prefix="/oid/auth")
 */

class OidLoginController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Welcome Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders the "marketing page" for the application and
	| is configured to only allow guests. Like most of the other sample
	| controllers, you are free to modify or remove it as you desire.
	|
	*/


	/**
	 * In a real application each IDP would probably have a dedicated
	 * controller, when for this small demo all of them are build in
	 * one single class.
	 * What ever pattern is used it is important to
	 * build IDP driver in controller class's constructor, even if
	 * you use it only in one method. If not socialuser's object
	 * unserialization will fail.
	 */
	public function __construct(IdpFactory $OidConnectFactory, LoaGuard $auth)	{
		$this->auth    = $auth;
		$this->idpfactory = $OidConnectFactory;

		// build each IDP from resources/config/OidConnect
		foreach ($this->idpfactory->getConfigByUid(null) as $idpinfo) {
			$this->idpdriver[$idpinfo['driver']] = $this->idpfactory->driver($idpinfo['driver']);
		}

		// an huggly hack to show user current status in each method
		echo '<h2>Social Login Controller</h2>';
		if ($auth->User() != null) {
			echo '<p>Session LOA= ' . $auth->loa() . ' User= ' . $auth->User()->pseudonym . "</p>";
		} else {
           echo "<p>Session LOA= 0 User= Not Connected</p>";
        }
    }

	/**
	 * Warning to change this route name your need to overload LOA middleware default route
	 * @Get("idplist", as="profile-loa-control")
	 */
	public function listIdp () {

		echo ("<p>Click on a Social Network to Authenticate<p>");
		echo ("<table style='border: 1px solid black'>");
		echo '<tr><td>IDP</td><td>LOA</td><td></td></tr>';
		foreach ($this->idpfactory->getConfigByUid(null) as $idpinfo) {
		   echo '<tr>';
           echo '<td>' . $idpinfo['name'] . '</td><td>'. $idpinfo['loa'] .'</td><td><a href="' . $idpinfo['name'] .'-login ">Login</a></td>';
	   	   echo '</tr>';
		}
		echo '<tr><td>Logout</td><td></td><td><a href="user-logout "><b>Logout</b></a></td></tr>';
		echo ("</table>");
	}

	/**
	 * @Get("user-logout")
	 */
	public function userLogout () {
		$this->auth->logout();
		return redirect()->route('profile-loa-control');
	}

	/**
	 * @Get("github-login")
	 */
	public function githubLogin (HttpRequest $request) {

		// at 1st call user has not the IDP code we redirect for authentication
		$hasCode = $request->has('code');
		if (!$hasCode) return $this->idpdriver['github-oauth2']->getIdpAuthorization();

		// second call we are returning from IDP and we should have a code
		$socialuser = $this->idpdriver['github-oauth2']->getIdpSocialUser($request);

		// we got a social user let's federate and log
        return $this->federateAndLog($socialuser);
	}

	/**
	 * @Get("facebook-login")
	 */
	public function facebookLogin (HttpRequest $request) {

		// at 1st call user has not the IDP code we redirect for authentication
		$hasCode = $request->has('code');
		if (!$hasCode) return $this->idpdriver['facebook-connect']->getIdpAuthorization();

		// second call we are returning from IDP and we should have a code
		$socialuser = $this->idpdriver['facebook-connect']->getIdpSocialUser($request);

		// we got a social user let's federate and log
        return $this->federateAndLog($socialuser);
	}

	/**
	 * @Get("orange-login")
	 */
	public function OrangeLogin (HttpRequest $request) {

		// at 1st call user has not the IDP code we redirect for authentication
		$hasCode = $request->has('code');
		if (!$hasCode) return $this->idpdriver['orange-parner']->getIdpAuthorization();

		// second call we are returning from IDP and we should have a code
		$socialuser = $this->idpdriver['orange-partner']->getIdpSocialUser($request);

		// we got a social user let's federate and log
        return $this->federateAndLog($socialuser);
	}


	protected function federateAndLog ($socialuser)	{

		// 1) If user is already federated let login and return
		if ($socialuser->fedkey != null) {

			// get user directly from his federation key
			$localuser = UserProfile::getLocalUserById($socialuser->getLocalUserId());

			// if logged user points to other account let's notify this information
			if ($this->auth->check()) {
				$loggeduser = UserProfile::getLocalUserById($this->auth->id());
				if ($loggeduser->id != $localuser->id) {
					Session::flash('alert', ['profile.user-federate-dup', $loggeduser->pseudonym]);
				}
			}
			return $this->userHasLoggedIn($localuser, $socialuser->provider->loa);
		}

		// 2) user is logged let add this IDP to his federation list
		if ($this->auth->check()) {
			$localuser = UserProfile::getLocalUserById($this->auth->id());

			// federate social user with its provider
			$socialuser->federate($localuser);

			// log user in application
			return $this->userHasLoggedIn($localuser, $socialuser->provider->loa);
		}

		// 3) User is neither log neither federated let request for consent
		return ($this->getConsent($socialuser));

	}

	/**
	 * Display User Consent Form
	 * @Get("consent")
	 */
    public function getConsent ($socialuser) {
		// socialuser can be serialized in order to be passed from on screen to the other
		Session::put ("socialuser", serialize($socialuser));

		// display a small HTML form to get user consent
		return  view('forms.user-consent')->with ('userinfo',$socialuser->profile);
	}

	/**
	 * Show the application demo form.
	 * @Post("consent", as="user-consent")
	 */
    public function setConsent (HttpRequest $request) {

		// retrieve original social user from session and overload with data return by enduser
        $socialuser = unserialize(Session::get ("socialuser"));
		$userloa        =  $socialuser->provider->loa;


		// provision social user in local DB [this function will fail if chosen pseudo is not unique]
		$localuser = userProfile::findOrProvisionSocialUser($socialuser, $request->email, $request->pseudonym, $userloa);

		// federate social user and send a verification email to confirm its address
		$socialuser->federate ($localuser);

		// Send to confirm email with a confirmation code if LOA is too low for us
		if ($userloa <= 1) userProfile::sendVerificationCode($localuser, $request->email);

		// finally log user and try to return to the intended page
        return $this->userHasLoggedIn($localuser, $userloa);

	}

    // Log user in, and try to redirect to original location or dispatching page if we fail
	public function userHasLoggedIn($localuser, $loa) {

		$this->auth->loginWithLoa($localuser, $loa, false);
		return redirect()->intended('/oid/loa/list')->with('success', array ('login.sucessfull', $localuser->pseudonym));
	}

	/**
	 * @Get("checkcode", as="email-check-code")
	 * This method send checkcode form for user to confirm its code
	 * if url as a code then this one is display
	 */
	public function checkCodeGet () {

		$checkcode = Input::get('key');
		if ($checkcode != null) {
			Session::flash ("_old_input", ['checkcode' => $checkcode]);
		}

		echo '!!!! You should write a form to collect code before controlling email';
		//return view ('users.checkcode');
	}

	/**
	 * @Post("checkcode", as="email-post-code")
	 * This method process checkcode form
	 */
	public function checkCodePost (VerificationCodeValidator $request) {

		// code was already check in validator, it should be ok
		$localuser= userProfile::updateEmailByVerificationCode ($request->checkcode);

		// if user is logged update his LOA
		if ($localuser->id == $this->auth->id()) {
			$this->auth->loginWithLoa($localuser, $localuser->loa);
		}

		// let's log user
		return redirect('/');
	}
}
