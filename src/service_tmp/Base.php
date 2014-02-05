<?php
/**
 * Created by: gellu
 * Date: 05.02.2014 11:40
 */

namespace Service;

use Slim\Slim;

/**
 * Class Base
 * @package Service
 */
abstract class Base {

	/** @var  \Slim\Slim */
	protected $_app;
	/** @var  \NotORM */
	protected $_db;

	public function __construct(Slim $app, \NotORM $db)
	{
		$this->_app = $app;
		$this->_db = $db;

	}

}

