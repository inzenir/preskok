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
	public function checkDB($tableName = 'sales_info')
	{
		$tables = iterator_to_array($this->adapter->query(
			"SHOW TABLES;",
			Adapter::QUERY_MODE_EXECUTE
		));
		
		$exists = false;
		
		foreach($tables as $table)
		{
			$temp = json_decode(json_encode($table), true);
			$value = array_pop($temp);
			
			if($value === $tableName)
			{
				$exists = true;
				break;
			}
		}
		
		return $exists;
	}
	
	/**
	 * @name ->createDB()
	 * Attempts to create table `sales_info`
	 */
	public function createDBSalesInfo()
	{
		$query = "CREATE TABLE IF NOT EXISTS `preskok`.`sales_info` (
					`sales_info_id` INT NOT NULL AUTO_INCREMENT ,
					`vehicle_id` INT NOT NULL ,
					`inhouse_seller_id` INT NOT NULL ,
					`buyer_id` INT NOT NULL ,
					`model_id` INT NOT NULL ,
					`sale_date` DATE NOT NULL ,
					`buy_date` DATE NOT NULL ,
					PRIMARY KEY (`sales_info_id`))";
		
		$this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
	}
	
	/**
	 * @name ->createDBBuyer()
	 * Attempts to create table `buyer_info`
	 */
	public function createDBBuyer()
	{
		$query = "CREATE TABLE `preskok`.`buyer_info` (
			`buyer_id` INT NOT NULL ,
			`first_name` TEXT NOT NULL ,
			`last_name` TEXT NOT NULL ,
			PRIMARY KEY (`buyer_id`))";
		
		$this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
	}
	
	/**
	 * @inheritdoc
	 */
	public function setConsoleResponseBuilder(&$consoleResponseBuilder)
	{
		$this->consoleResponseBuilder =& $consoleResponseBuilder;
	}
}