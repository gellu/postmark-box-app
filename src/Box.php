<?php
/**
 * Created by: gellu
 * Date: 13.09.2013 13:52
 */

$app->group('/box', function() use ($app, $db) {

	$app->post('/create', function() use ($app, $db) {

		$post = $app->request->post();

		if(!$post['sender'] || !$post['receiver'] || !$post['body'])
		{
			echo json_encode(array('status' => 'error', 'result' => 'Wrong params!'));
			$app->stop();
		}

		$senderHashEmail = $app->boxService->getHashEmail($post['sender']);

		if($senderHashEmail === null)
		{
			echo json_encode(array('status' => 'error', 'result' => "Can't create sender hash email"));
			$app->stop();
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

		$app->boxService->touchBox($post['sender']);
		$app->messageService->saveEmail($post['sender'], $post['receiver'], $body);

		echo json_encode(array('status' => $status ? 'ok' : 'error sending email'));

	});

	$app->map('/send', function() use ($app, $db) {

		if(!$app->request->getBody())
		{
			echo json_encode(array('status' => 'error', 'msg' => 'Request body is missing'));
			$app->stop();
		}

		try
		{
			$inbound = new Postmark\Inbound($app->request->getBody());
		} catch (Exception $e) {}

		if(!$inbound)
		{
			$app->response->setStatus('500');
			echo json_encode(array('status' => 'error', 'msg' => 'Inbound msg error: '. $app->request->getBody()));
			$app->stop();
		}

		$source = (array) $inbound->Source;

		if(!$source['MailboxHash'])
		{
			echo json_encode(array('status' => 'ok', 'msg' => 'No MailboxHash skipping...'));
			$app->stop();
		}

		$receiver = $app->boxService->getEmailByHash($source['MailboxHash']);

		if(!$receiver)
		{
			$app->response->setStatus('500');
			echo json_encode(array('status' => 'error', 'msg' => 'No email for '. $source['MailboxHash'] .' hash'));
			$app->stop();
		}

		$senderHashEmail = $app->boxService->getHashEmail($inbound->FromEmail());

		if($senderHashEmail === null)
		{
			echo json_encode(array('status' => 'error', 'result' => "Can't create sender hash email"));
			$app->stop();
		}

		$status = Postmark\Mail::compose($app->config('appData')['postmark_api_key'])
							   ->from($senderHashEmail, $app->config('appData')['name'])
							   ->addTo($receiver)
							   ->subject($source['Subject'])
							   ->messageHtml(htmlspecialchars_decode($source['HtmlBody']))
							   ->send();

		$app->boxService->touchBox($inbound->FromEmail());
		$app->messageService->saveEmail($inbound->FromEmail(), $receiver, htmlspecialchars_decode($source['HtmlBody']), $app->request->getBody());

		echo json_encode(array('status' => $status ? 'ok' : 'error sending email'));

	})->via('GET', 'POST');

});