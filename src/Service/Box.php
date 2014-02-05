<?php
/**
 * Created by: gellu
 * Date: 05.02.2014 11:43
 */

namespace Service;

/**
 * Class Box
 * @package Service
 */
class Box extends Base
{
	/** Salt for email hashing */
	const SALT = '289dhsjkalu3yiqr';

	/**
	 * Get hash for email,. If it's not already created, create it.
	 *
	 * @param $email
	 *
	 * @return null|string
	 */
	public function getHashEmail($email)
	{
		$res = $this->_db->box()->where('email', $email)->fetch();

		// hash not found -> create it
		if(!$res)
		{
			$hash = $this->generateHash($email);
			$res = $this->saveHash($email, $hash);
			return $res ? $this->prepareEmail($hash) : null;
		}

		return $this->prepareEmail($res['hash']);

	}

	/**
	 * @param $email
	 *
	 * @return string
	 */
	public function generateHash($email)
	{
		return md5($email . self::SALT);
	}

	/**
	 * Save email hash as new box.
	 *
	 * @param $email
	 * @param $hash
	 *
	 * @return bool
	 */
	public function saveHash($email, $hash)
	{
		$box = array(
			'email' 		=> $email,
			'hash'  		=> $hash,
			"created_at"	=> new \NotORM_Literal("NOW()"),
		);

		return $this->_db->box()->insert($box) ? true : false;
	}

	/**
	 * Build email address according to app template and hash
	 *
	 * @param $hash
	 *
	 * @return string
	 */
	public function prepareEmail($hash)
	{
		return str_replace('{{hash}}', $hash, $this->_app->config('appData')['email_pattern']);
	}

	/**
	 * Update msg_count and last_sent_at of box
	 *
	 * @param $email
	 *
	 * @return bool
	 */
	public function touchBox($email)
	{
		$box = array(
			'msg_count'		=> new \NotORM_Literal('msg_count+1'),
			'last_sent_at'	=> new \NotORM_Literal('NOW()'),
		);

		return $this->_db->box()->where('email', $email)->fetch()->update($box) ? true : false;
	}

	/**
	 * @param $hash
	 *
	 * @return string|null
	 */
	public function getEmailByHash($hash)
	{
		$res = $this->_db->box()->where('hash', $hash)->fetch();

		return $res ? $res['email'] : null;
	}
}