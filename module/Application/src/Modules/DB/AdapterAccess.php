<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 8:30 PM
 */

namespace Application\Modules\DB;


use Zend\Db\Adapter\Adapter;

class AdapterAccess
{
	/** @var null|Adapter  */
	private static $adapter = null;
	
	/**
	 * @return Adapter
	 */
	public static function getAdapter()
	{
		if(self::$adapter === null)
		{
			self::$adapter = new Adapter(
				[
					'driver'         => 'Pdo',
					'dsn'            => 'mysql:dbname=preskok;host=localhost',
					'username'       => 'preskok',    // da ne sharamo credentialov bi bilo bolje
					'password'       => 'preskok123', // imeti v local configu, vendar je OK za potrebe testa
					'driver_options' => array(
						\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
					)
				]
			);
		}
		
		return self::$adapter;
	}
}