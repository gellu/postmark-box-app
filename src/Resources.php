<?php
/**
 * Created by: gellu
 * Date: 29.01.2014 12:34
 */

class Helper
{
	/** @var PDO */
	protected $_db;
	/** @var  \Slim\Slim */
	protected $_app;


	public function __construct($app, $db)
	{
		$this->_app = $app;
		$this->_db = $db;
	}
}


class EmailHelper extends Helper
{
	const SALT = '289dhsjkalu3yiqr';

	public function getHashEmail($email)
	{
		$sth = $this->_db->prepare('SELECT hash FROM boxes WHERE email = :email');
		$sth->execute(array('email' => $email));
		$res = $sth->fetch();

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
		$sth = $this->_db->prepare('INSERT INTO boxes SET email = :email, hash = :hash, created_at = NOW()');
		$sth->execute(array(
			'email' => $email,
			'hash'  => $hash,
		));
		return $sth->rowCount() ? true : false;
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
		$sth = $this->_db->prepare('UPDATE boxes SET msg_count = msg_count+1, last_sent_at = NOW() WHERE email = :email');
		$sth->execute(array('email' => $email));
		return $sth->rowCount() ? true : false;
	}

	public function getEmailByHash($hash)
	{
		$sth = $this->_db->prepare('SELECT email FROM boxes WHERE hash = :hash');
		$sth->execute(array('hash' => $hash));
		$res = $sth->fetch();

		return $res ? $res['email'] : null;
	}

}