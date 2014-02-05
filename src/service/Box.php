<?php
/**
 * Created by: gellu
 * Date: 05.02.2014 11:43
 */

namespace Service;

class Box extends Base
{
	const SALT = '289dhsjkalu3yiqr';

	public function showSalt()
	{
		echo self::SALT;
	}

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
	 * @param $hash
	 *
	 * @return string
	 */
	public function prepareEmail($hash)
	{
		return str_replace('{{hash}}', $hash, $this->_app->config('appData')['email_pattern']);
	}

	public function touchBox($email)
	{
		$box = array(
			'msg_count'		=> new \NotORM_Literal('msg_count+1'),
			'last_sent_at'	=> new \NotORM_Literal('NOW()'),
		);

		return $this->_db->box()->where('email', $email)->fetch()->update($box) ? true : false;
	}

	public function getEmailByHash($hash)
	{
		$res = $this->_db->box()->where('hash', $hash)->fetch();

		return $res ? $res['email'] : null;
	}
}