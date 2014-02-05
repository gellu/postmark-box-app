<?php
/**
 * Created by: gellu
 * Date: 05.02.2014 11:43
 */

namespace Service;

class Message extends Base
{

	public function saveEmail($sender, $receiver, $body, $rowBody = '')
	{
		$message = array(
			'sender' 		=> $sender,
			'receiver'		=> $receiver,
			'body'			=> $body,
			'raw_body'		=> $rowBody,
			'created_at'	=> new \NotORM_Literal('NOW()'),
		);

		return $this->_db->message()->insert($message) ? true : false;

	}
}