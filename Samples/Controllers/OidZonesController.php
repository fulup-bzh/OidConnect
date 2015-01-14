<?php namespace App\Http\Controllers;

use OidConnect\LoaAuth\LoaAuthContract as LoaGuard;

/**
 * @Controller(prefix="/oid/loa")
 */

class OidZonesController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| LOA Zones Controller
	|--------------------------------------------------------------------------
	|
	| This controller has its methods protected by LOA
	|
	*/

	public function __construct (LoaGuard $auth) {
		echo '<h2>Access Zone Controller</h2>';
		if ($auth->User() != null) {
			echo '<p>Session LOA= ' . $auth->loa() . ' User= ' . $auth->User()->pseudonym . "</p>";
		} else {
			echo "<p>Session LOA= 0 User= Not Connected</p>";
		}
	}

	/**
	 * Show the application demo form.
	 * @Get("list")
	 */
	public function index ()	{
		echo "Click on a zone depending on your LOA you will be redirected or not";
		echo "<ul>";
		echo "<li><a href='zone-A'>Zone-A</a></li>" ;
		echo "<li><a href='zone-B'>Zone-B</a></li>" ;
		echo "<li><a href='zone-A'>Zone-B</a></li>" ;
		echo "</ul>";

	}

	/**
	 * Show the application demo form.
	 * @Middleware("auth.loa1")
	 * @Get("zone-A")
	 */
	public function zoneA ()	{
		echo "Vous etes dans la zone-A";
	}

	/**
	 * Show the application demo form.
	 * @Middleware("auth.loa2")
	 * @Get("zone-B")
	 */
	public function zoneB ()	{
		echo "Vous etes dans la zone-B";
	}

	/**
	 * Show the application demo form.
	 * @Middleware("auth.loa3")
	 * @Get("zone-C")
	 */
	public function zoneC ()	{
		echo "Vous etes dans la zone-C";
	}

}
