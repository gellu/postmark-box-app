<?php
/**
 * Created by: gellu
 * Date: 05.02.2014 11:43
 */

namespace Service;

/**
 * Class Message
 * @package Service
 */
class Message extends Base
{
	/**
	 * Saves email as message
	 *
	 * @param        $sender
	 * @param        $receiver
	 * @param        $body
	 * @param string $rowBody
	 *
	 * @return bool
	 */
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