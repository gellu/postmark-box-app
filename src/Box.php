<?php
/**
 * Created by: gellu
 * Date: 13.09.2013 13:52
 */

$app->group('/box', function() use ($app, $db) {

	$app->get('/test', function() use ($app, $db) {

	});

	$app->post('/create', function() use ($app, $db) {

		$post = $app->request->post();

		if(!$post['sender'] || !$post['receiver'] || !$post['body'])
		{
			$app->responseBody = array('msg' => 'Wrong params!');
			$app->halt(500);
		}

		$senderHashEmail = $app->boxService->getHashEmail($post['sender']);

		if($senderHashEmail === null)
		{
			$app->responseBody = array('msg' => "Can't create sender hash email");
			$app->halt(500);
		}

		### PARSE TEMPLATE
		$msgTplElements = array('body' => $post['body']);
		if(isset($post['body_vars']))
		{
			$msgTplElements = array_merge($msgTplElements, json_decode($post['body_vars'], true));
		}

		$body = $app->config('appData')['msg_body_template'];
		foreach($msgTplElements as $elementName => $elementValue)
		{
			$body = str_replace('{{' . $elementName .'}}', $elementValue, $body);
		}
		### END PARSE TEMPLATE

		$status = Postmark\Mail::compose($app->config('appData')['postmark_api_key'])
			->from($senderHashEmail, $app->config('appData')['name'])
			->addTo($post['receiver'])
			->subject($app->config('appData')['msg_subject_template'])
			->messageHtml($body)
			->send();

		if(!$status)
		{
			$app->responseBody = array('msg' => 'error sending email');
			$app->halt(500);
		}

		$app->boxService->touchBox($post['sender']);
		$app->messageService->saveEmail($post['sender'], $post['receiver'], $body);

	});

	$app->map('/send', function() use ($app, $db) {

		if(!$app->request->getBody())
		{
			$app->responseBody = array('msg' => 'Request body is missing');
			$app->halt(500);
		}

		try {
			$inbound = new Postmark\Inbound($app->request->getBody());
		} catch (Exception $e) {}

		if(!$inbound)
		{
			$app->responseBody = array('msg' => 'Inbound msg error: '. $app->request->getBody());
			$app->halt(500);
		}

		$source = (array) $inbound->Source;

		if(!$source['MailboxHash'])
		{
			$app->responseBody = array('msg' => 'No MailboxHash skipping...');
			$app->halt(404);
		}

		$receiver = $app->boxService->getEmailByHash($source['MailboxHash']);

		if(!$receiver)
		{
			$app->responseBody = array('msg' => 'No email for '. $source['MailboxHash'] .' hash');
			$app->halt(404);
		}

		$senderHashEmail = $app->boxService->getHashEmail($inbound->FromEmail());

		if($senderHashEmail === null)
		{
			$app->responseBody = array('msg' => "Can't create sender hash email");
			$app->halt(500);
		}

		$status = Postmark\Mail::compose($app->config('appData')['postmark_api_key'])
							   ->from($senderHashEmail, $app->config('appData')['name'])
							   ->addTo($receiver)
							   ->subject($source['Subject'])
							   ->messageHtml(htmlspecialchars_decode($source['HtmlBody']))
							   ->send();

		if(!$status)
		{
			$app->responseBody = array('msg' =>'Error sending email');
			$app->halt(500);
		}

		$app->boxService->touchBox($inbound->FromEmail());
		$app->messageService->saveEmail($inbound->FromEmail(), $receiver, htmlspecialchars_decode($source['HtmlBody']), $app->request->getBody());


	})->via('GET', 'POST');

});