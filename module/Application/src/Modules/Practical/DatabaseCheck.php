<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 11:09 PM
 */

namespace Application\Modules\Practical;


use Application\Modules\Console\ConsoleResponseBuilder;
use Application\Modules\DB\AdapterAccess;
use Zend\Db\Adapter\Adapter;

class DatabaseCheck implements AcceptsConsoleResponseBuilderInterface
{
	/** @var null|ConsoleResponseBuilder */
	private $consoleResponseBuilder = null;
	
	/** @var \Zend\Db\Adapter\Adapter */
	private $adapter;
	
	public function __construct()
	{
		$this->adapter = AdapterAccess::getAdapter();
	}
	
	/**
	 * @name ->checkDB()
	 * Checks if table `preskok` exists in database;
	 *
	 * @return boolean
	 */
	public function checkDB()
	{
		$tables = iterator_to_array($this->adapter->query(
			"SHOW TABLES;",
			Adapter::QUERY_MODE_EXECUTE
		));
		
		$exists = false;
		
		foreach($tables as $table)
		{
			$value = array_pop(json_decode(json_encode($table), true));
			
			if($value === 'preskok')
			{
				$exists = true;
				break;
			}
		}
		
		return $exists;
	}
	
	public function createDB()
	{
	
	}
	
	/**
	 * @inheritdoc
	 */
	public function setConsoleResponseBuilder(&$consoleResponseBuilder)
	{
		$this->consoleResponseBuilder =& $consoleResponseBuilder;
	}
}