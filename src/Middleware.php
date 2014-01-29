<?php
/**
 * Created by: gellu
 * Date: 12.09.2013 16:58
 */

/**
 * Class APIResponseMiddleware
 * Set proper header for API response
 */
class APIResponseMiddleware extends \Slim\Middleware
{
	public function call()
	{
		$this->next->call();

		$this->app->response()->header('Content-Type', 'application/json');
	}
}

/**
 * Class APIAuthMiddleware
 * Authorize API call with tokens
 */
class APIAuthMiddleware extends \Slim\Middleware
{
	/**
	 * @var PDO
	 */
	private $_db;

	private $_appKey;

	public function __construct($db)
	{
		$this->_db = $db;
	}

	public function call()
	{
		$this->_appKey = $this->app->request()->get('app_key');

		if(!$this->_appKey)
		{
			echo json_encode(array('status' => 'error', 'result' => 'Please provide app key'));
			return;
		}

		$sth = $this->_db->prepare('SELECT * FROM app WHERE app_key = :app_key LIMIT 1');
		$sth->execute(array('app_key' => $this->_appKey));
		$appData = $sth->fetch(PDO::FETCH_ASSOC);

		if(!$appData)
		{
			echo json_encode(array('status' => 'error', 'result' => 'App key is invalid'));
			return;
		}

		$this->app->config('appData', $appData);

		$this->next->call();
	}

}