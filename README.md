Intro
 OidConnect is a Laravel5 component for Social login using OAuth2/OpenID-Connect.
 It implements a real federated model, where users may have more than one social providers
 pointing to the same local account. It also allows users to have a different local email/name
 than the one used at the IDP level.

 Last but not least it supports multiple level of assurances. Allowing application to choose
 the level of thrust depending on the source of Social authentication or the quality of the
 profile they get.

 Demo:  http://oidconnect.breizhme.net

Manual Installation:
  Until L5 remains in beta OidConnect will not be installable by 'composer'
  Manual installation is not hard and it avoids usage of 'composer update'.
  Until beta is ongoing update may impact many vendors packages and introduces
  unwanted features. The good new: manual installation remains very simple.

L5/Socialite: Why did I stop using Socialite ?
  I started my project with L5/Socialite. Unfortunately Socialite had too many
  constrains. I started by overloading Socialite classes, but I arrived to a point where keeping
  inheritance had more defaults than advantages. My main concerns about Socialite:
   - Lack of easy OO extendability. Adding a new IDP requires to edit core source components. Changing
     Socialiste's User model is not really possible. Design with multiple very small methods makes
     debug process harder than it should.
   - Weak security model. Socialite exclusively relies on IDP provided email for its local authentication.
     This might be acceptable with provider that certify emails. But it cannot work with providers
     like GitHub that allow end users to select any email they want without any control. An other
     class of unsolved issue is with providers like Yahoo or Orange that do not return user's email.
   - No support for OpenID-Connect. With OID-Connect we receive from IDPs user's UID attached
     with the authorization token. If this user is already known in federation table, then it is
     not necessary to move lower in OAUTH2 flow as we have enough information to return directly
     local user's ID without request IDP's Identity APIs.


INSTALLATION:

00) Request Client ID/Secret from the IDPs you want to log from. Check in Sample/Config/OIDconnect
    for the provider you want, and the URL of corresponding developer's console to request your
    application keys.


-------- prerequisite install necessary components --------
0) Download an install a fresh Laravel-5 distrib

   composer create-project laravel/laravel <yourdirectryname> dev-develop

1) Verify you did not mess up and did not install version 4.2

    grep '5' composer.json
    -> "laravel/framework": "~5.0"

2) Install 'guzzlehttp' component [this is the only external dependency]

   Update your composer.json and add modify your require array as such
    "require": {
        "laravel/framework": "~5.0",
        "guzzlehttp/guzzle": "~5.0"
   },

3) Refresh your distrib

   composer update   # one day someone should explain me why 'composer' it soo slow and require so much resources.


4) Download and uncompressed OidConnect in your L5 project root directory.

   wget https://github.com/fulup-bzh/OidConnect/archive/master.zip

   Do not place OidConnect in 'app' or 'vendor' directories to avoid namespace conflict
   with existing components. Technically OidConnect directory can be anywhere. Nevertheless
   common practice is to keep it in your project root, or right under if you want to share
   the same component in between multiple applications.

5) Update autoload class path, to reflect the path you choose.

   edit composer.json and add a new directory in 'psr-4'
     "psr-4": {
		"AppNameSpace\\": "app/",
		"OidConnect\\": "OidConnect/"
  	 }

   Note: replace  "OidConnect/" with what ever path you used. OidConnect can even reside outside
   of your application tree. For exemple "OidConnect\\": "../OidConnect/" allow to share OidConnect
   in between multiple application, which can be very convenient when developing.

   update autoloader cache with command: 'composer dumpautoload'

5) If you want to use Orange provider for test, create an alias on localhost
    ex: in your /etc/hosts   "127.0.0.1 oidconnect.localnet"

6) Check you basic install works

     - start a local server with : php -t public -S oidconnect.localnet:8080
     - point a browser on: http://oidconnect.localnet:8080

     Note: you can replace oidconnect.localnet if you only test with GitHub+Facebook

     If you get L5 welcome page, you're ready for next step


----- Configure your distribution ------

A) Create an SQL database of configure sqlite and check it worked
   -> mysql --user=oiddemo --password='123456' oiddemo

B) Create the .env file [L5 is unclear about config subdir]
   APP_ENV=local
   APP_DEBUG=true
   APP_KEY=123456789 # result of ./artisan key:generate
   DB_HOST=localhost
   DB_DATABASE=oiddemo
   DB_USERNAME=oiddemo
   DB_PASSWORD=123456

C) Declare OidConnect in your L5 namespace

  Edit composer.json and add a line to PSR-4 array
  "psr-4": {
      "App\\": "app/",
      "OidConnect\\": "OidConnect/"
  }

D) Refresh class autoloader with composer

   composer dumpautoload
    -> Generating autoload files

E) Create DB tables: federation, users and email verification tables

    The simplest way is to replace all L5 distrib migration files with
    the one from this DEMO. In your application you may want your
    own users repository organization but to keep the demo as simple
    as possible let's use a shortcut path.

    rm database/migrations/*
    cp OidConnect/Samples/Datebase/2014_1* database/migrations/

    ./artisan migrate
        Migration table created successfully.
        Migrated: 2014_10_12_100000_create_password_resets_table
        Migrated: 2014_12_09_170132_create_users_table
        Migrated: 2014_12_09_180945_create_federation_key_table
        Migrated: 2014_12_09_190950_create_check_code_table

F) Update your service providers and alias in config/app

     /*
      * Add OidConnect Providers...
      */
     'OidConnect\DriverManager\IdpFactoryProvider',
     'OidConnect\UserManagement\UserProfileProvider',
     'OidConnect\LoaAuth\LoaAuthProvider',

    /*
     * Add OidConnect Aliases
     */
     'IdpFactory' => 'OidConnect\DriverManager\IdpFactoryFacade',
     'UserProfile'=> 'OidConnect\UserManagement\UserProfileFacade',
     'Auth'       => 'OidConnect\LoaAuth\LoaAuthFacade',

    /*
     * comment out L5 Auth Service provider and Facade Alias
     */
	//'Illuminate\Auth\AuthServiceProvider',
	//'Auth'      => 'Illuminate\Support\Facades\Auth',

    Note: we replace the original L5 Auth module, because OidConnect
    inherits from Auth and is 100% backward compatible.



G) Update Middleware. OidConnect is shipped with a set of standard
   middleware to handle LOA access restriction on controllers.

     // In app/kernel.php add following filters after L5 ones
     protected $routeMiddleware = [
		'auth.basic' => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
		'auth.guest' => 'GeoToBe\Http\Middleware\RedirectIfAuthenticated',

		'auth.loa0'  => 'OidConnect\LoaAuth\Loa00Middleware',
		'auth.loa1'  => 'OidConnect\LoaAuth\Loa01Middleware',
		'auth.loa2'  => 'OidConnect\LoaAuth\Loa02Middleware',
		'auth.loa3'  => 'OidConnect\LoaAuth\Loa03Middleware',
	 ];

H) Get API keys from IDPs authentication providers.

   This is probably the longest part. You may check Samples/Config/OidConnect.php.
   Select the one you like and request an application KEY. You will find
   developer console URL at the top of each provider sample. Note that only few
   providers accept to work with localhost redirect. GitHub is the easiest one
   for test. Facebook and Orange accept Localhost redirect in development mode.
   Google and Yahoo do not accept redirect on localhost.

   Warning: For Orange you should not use localhost:8080 as redirect for your
   test, but oid.localnet:8080 that you point in your /etc/hosts on 127.0.0.1
   While this alias approach is not mandated for them, GitHub and Facebook support
   it.

   When you're done, your resources/config/OidConnect.php file should
   have a valid key and redirect URL for each providers you wish
   to use.


---- We are now ready to write our 1st OpenId Connect app ----------------

  Copy the two controllers and templates sample to your "app" dir. Note that sample
  controller use "App" namespace, you may want to change this.

  The demo has two controllers. First one handle login, second one simulate
  an application controller that need to be protected by LOA. The controller
  have very limited graphic design in order to keep them as basic as possible.

  OidZoneController: it the simplest one. Each method is protected by a simple
  middleware corresponding to the level of wanted LOA. Note that an LOA=0
  does not mean the user is not logged, it only mean that we have absolutely
  no confidence in his identity. Nevertheless LOA=0 can still be acceptable
  to leave comment on a blog, or store user preferences. They is no real need
  for a login page, when a user hit a protected page, when needed he is
  redirected for authentication.

  WARNING: I used annotation in my controller sample, and it looks like they
  where will be removed from L5. Until annotation come back as un external
  package. You may have either to use an older version of L5 or build your
  route and middleware as before.

  OidLoginController:

  __construct: read OidConnect config file and build drivers table. This is
  a demo scenario. In real live we should have one controller per IDP.

  IdpList()

  this method only display a small table with every IDPs configured
  in your Config file. Note that IDP's UID are free but should remain unique.

  Idp-Login

  they are all based on the same model. Idp-Login URL is the one
  you have to declare when requesting an API client KEY on provider's development
  console. The scenario is the same as within Socialite if you ever used it.

  When user client click on the 'github' link generated by IdpList(). He is redirected
  to githubLogin(). As this is a simple redirect he comes without any authorisation
  code. Method getIdpAuthorization() is then called and redirect user's browser to IDP
  login service with the adequate argument extracted from config/OidConfig.
  When user returns from IDP authentication service, he has an authorisation code
  and controller calls getIdpSocialUser($request). This last method returns
  directly a socialuser object that contains enduser profile and eventually
  a localuser profile if ever this user was already federated.

	public function githubLogin (HttpRequest $request) {

		// at 1st call user has not the IDP code we redirect for authentication
		$hasCode = $request->has('code');
		if (!$hasCode) return $this->idpdriver->getIdpAuthorization();

		// second call we are returning from IDP and we should have a code
		$socialuser = $this->idpdriver->getIdpSocialUser($request);

		// we got a social user let's federate and log
        return $this->federateAndLog($socialuser);
	}

  FederateAndLog($socialuser)

  This method will take a socialuser as returned from getIdpSocialUser() and handle
  3 cases.

  1) socialuser already federated, in this case we can login.
  2) socialuser is new, but localuser is currently login. In this case we
     add a new federation link to this existing localuser
  3) socialuser is unknown and we have no valid login session. In this case
     we need to ask for user consent and eventually check his profile.
     This later step is done through set/getUserConsent

  Set/getUserConsent()

  First method will serialize and push current socialuser in session. Then it posts a form.
  When user has confirm his profile and return getUserConsent() will try to create and federate
  local/socialuser. If user creation succeeds this method will call userProfile::sendVerificationCode
  to send a verification code by email. Note that depending on IDP's LOA this step might not be
  necessary. Most IDP return verified email.


  WARNING: demo controller use L5 annotation for routes and middleware.
  you should declare both controllers in app/RouteServiceProvider and apply
  "php artisan route:scan" to built routes and filter before usage.

  	protected $scan = [
		'App\Http\Controllers\OidZonesController',
		'App\Http\Controllers\OidLoginController',
    ];

  Mail: Demo will try to send a validation mail to new users. If you did not
    configure properly resources/config/mail this part will fail. Alternatively
    you may comment out userProfile::sendVerificationCode line in Login controller.


  ------------- Extending OidConnect ---------------------------------
  OidConnect should be pretty easy to extend.

  LOA middleware, build your own class that extends LoaAclMiddleware.

  New Provider, build you own one that extends from _DriverSuperClass and declare
  your new provider in resources/config/OidConnect you do not have to be in
  OidConnect namespace and your customization can remains 100% independent of OidConnect
  code. For OAUTH2 providers you may use Facebook or LinkedIn as sample. For native
  OpenIdConnect you should use Google or Orange. Orange is my reference platform
  for OpenIdConnect implementation.


 I hope I'm not been too long, and that you did not get lost in the middle of my README.