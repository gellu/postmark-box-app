<?php
/**
 * Created by: gellu
 * Date: 12.09.2013 15:55
 */

require '../vendor/autoload.php';

$config = require 'config.php';

$app = new \Slim\Slim($config['slim']);

# fix for setting correct PATH_INFO
$requestPath = parse_url($_SERVER['REQUEST_URI'])['path'];
$env = $app->environment;
$env['PATH_INFO'] = substr($requestPath, 0, strlen($env['SCRIPT_NAME'])) == $env['SCRIPT_NAME']
	? substr_replace($requestPath, '', 0, strlen($env['SCRIPT_NAME'])) : $requestPath ;
# fix end

$app->notFound(function () use ($app) {
	$app->response()->setStatus(404);
	$app->responseBody = array('msg' => 'Method not found');
});

try {
	$pdo = new PDO('mysql:dbname='. $config['pdo']['name'] .';host='. $config['pdo']['host'].($config['pdo']['port'] ? ':'.$config['pdo']['port'] : ''), $config['pdo']['user'], $config['pdo']['pass']);
	$pdo->exec("SET CHARACTER SET utf8");
	$db = new NotORM($pdo);
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}

require '../src/Middleware.php';


// Authorize API call with app_key
$app->add(new \APIAuthMiddleware($db));
// Send proper headers for response
$app->add(new \APIResponseMiddleware());

// Services
$app->boxService = new \Service\Box($app, $db);
$app->messageService = new \Service\Message($app, $db);

require '../src/Box.php';

$app->run();

if($app->response()->getStatus() != 200)
{
	$db->error()->insert([
		'error'      => $app->response()->getBody(),
		'request'    => serialize($app->request()),
		'response'   => serialize($app->response()),
		'created_at' => new NotORM_Literal("NOW()"),
	]);
}
