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

		$response = array();

		$response['status'] = $this->app->response()->getStatus();

		if(is_array($this->app->responseBody))
		{
			$response = array_merge($response, $this->app->responseBody);
		}

		$this->app->response()->setBody(json_encode($response));

	}
}

/**
 * Class APIAuthMiddleware
 * Authorize API call with tokens
 */
class APIAuthMiddleware extends \Slim\Middleware
{
	/**
	 * @var NotORM
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
			$this->app->responseBody = array('msg' => 'Please provide app key');
			$this->app->response()->setStatus(500);
			return;
		}

		$appData = $this->_db->app()->where('app_key', $this->_appKey)->limit(1)->fetch();

		if(!$appData)
		{
			$this->app->responseBody = array('msg' => 'App key is invalid');
			$this->app->response()->setStatus(500);
			return;
		}

		$this->app->config('appData', $appData);

		$this->next->call();
	}

}