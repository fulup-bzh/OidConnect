<?php namespace OidConnect\LoaAuth;

/**
 * Author : Fulup Ar Foll (jan-2015)
 * Project: OidConnect
 * Object : L5 Service Provider to register LOA enable authentication service
 *          extend standard Laravel-5 Guard/Auth to support Level of Assurance.
 *
 * Copyright: what ever you like, util you fix bugs by yourself :)
 */

use Closure;
use Illuminate\Contracts\Routing\Middleware;
use OidConnect\LoaAuth\LoaAuthContract as LoaGuard;

use Session;
use App;

class LoaAclMiddleware implements Middleware {

	protected $loaRequested=99; // default LOA is higher than possible.
	protected $loaRoute="profile-loa-control"; // default LOA is higher than possible.

	// note: LoaGuard should point in the interface and not on concrete class
	public function __construct(LoaGuard $auth) {
		$this->auth = $auth;
	}

	public function loaAccepted() {
		// user is logged in, compare session LOA with requested one
		if ($this->auth->loa() < $this->loaRequested) return false;
		return true;
	}

	public function handle($request, Closure $next)	{


		if (!$this->auth->check() || ! $this->loaAccepted()) {
		  // store intended destination as Middleware does not look doing it ???
		  $this->auth->intended ($request->url(), $this->loaRequested);

           $ret= redirect()->route ($this->loaRoute)->with('warning',['login.loa-toolow', $this->auth->loa()]);
           return ($ret); 
		}

        // we're ok let request get in
		return $next($request);
	}
}
