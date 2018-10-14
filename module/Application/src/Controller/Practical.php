<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 8:47 PM
 */

namespace Application\Controller;


use Application\Modules\Console\ConsoleResponseBuilder;
use Application\Modules\DB\AdapterAccess;
use Application\Modules\Practical\DatabaseCheck;
use Application\Modules\Practical\Parser;
use Faker\DefaultGenerator;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\sl_SI\Person;
use Zend\Db\Adapter\Adapter;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * Class Practical
 *
 * @package Application\Controller
 * @author  : inzenir
 */
class Practical extends AbstractActionController
{
	/**
	 * @name ->parserAction()
	 *
	 * @return string
	 */
	public function parserAction()
	{
		$response = new ConsoleResponseBuilder();
		$request = $this->getRequest();
		
		$verbose = $request->getParam("v") | $request->getParam("verbose");
		$response->setVerboseMode($verbose);
		
		try
		{
			$p = new Parser();
			$p->setConsoleResponseBuilder($response);
			$p->getDataAsArray();
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: " . $e->getMessage());
		}
		
		return $response->getMessages();
	}
	
	/**
	 * @name ->dbCheckAction()
	 *
	 * @return string
	 */
	public function dbCheckAction()
	{
		$response = new ConsoleResponseBuilder();
		$request = $this->getRequest();
		
		$verbose = $request->getParam("v") | $request->getParam("verbose");
		
		try
		{
			$dbCheck = new DatabaseCheck();
			$dbCheck->setConsoleResponseBuilder($response);
			
			$response->appendVerbose($verbose, "Checking if table exists");
			
			if($dbCheck->checkDB())
			{
				$response->appendMessage("Table exists. Nothing more to do.");
			}
			else
			{
				$response->appendVerbose($verbose, "Table does not exist. Attempting to create");
				$dbCheck->createDBSalesInfo();
				$response->appendMessage("Table created.");
			}
			
			$p = new Parser();
			$p->setConsoleResponseBuilder($response);
			$data = $p->getDataAsArray();
			
			$adapter = AdapterAccess::getAdapter();
			try
			{
				$adapter->getDriver()->getConnection()->beginTransaction();
				
				$query = "INSERT INTO `sales_info`
							(`vehicle_id`, `inhouse_seller_id`, `buyer_id`, `model_id`, `sale_date`, `buy_date`)
							VALUES ";
				
				// ne escapam, ker se v tem primeru zanašam (vem ja, ne bi se smel), na pravilnost zapisa
				foreach($data as $value)
				{
					$query .= "('"
							  . $value["VehicleID"]
							  . "', '"
							  . $value["InhouseSellerID"]
							  . "', '"
							  . $value["BuyerID"]
							  . "', '"
							  . $value["ModelID"]
							  . "', '"
							  . $value["SaleDate"]
							  . "', '"
							  . $value["BuyDate"]
							  . "'),";
				}
				
				$query = substr($query, 0, -1);
				
				$adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
				
				$adapter->getDriver()->getConnection()->commit();
			}
			catch(\Exception $exc)
			{
				$adapter->getDriver()->getConnection()->rollback();
				throw  $exc;
			}
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: " . $e->getMessage());
		}
		
		return $response->getMessages();
	}
	
	/**
	 * @name ->populateBuyersTableAction()
	 *
	 * @return string
	 */
	public function populateBuyersTableAction()
	{
		$response = new ConsoleResponseBuilder();
		$request = $this->getRequest();
		$verbose = $request->getParam("v") | $request->getParam("verbose");
		
		try
		{
			$db = new DatabaseCheck();
			
			if($db->checkDB('buyer_info'))
			{
				$response->appendVerbose($verbose, "Table 'buyer_info' exists, no need to create it.");
			}
			else
			{
				$response->appendVerbose($verbose, "Table 'buyer_info' does not exist, attempting to create.");
				$db->createDBBuyer();
			}
			
			$adapter = AdapterAccess::getAdapter();
			
			$buyersIds = array();
			$buyers = $adapter->query(
				"SELECT DISTINCT `buyer_id` FROM `sales_info`;",
				Adapter::QUERY_MODE_EXECUTE
			);
			
			$response->appendVerbose($verbose, "Buyers found: " . $buyers->count());
			
			foreach($buyers->toArray() as $buyer)
			{
				$buyersIds[$buyer["buyer_id"]] = $buyer["buyer_id"];
			}
			
			$populatedBuyers = $adapter->query(
				"SELECT `buyer_id` FROM `buyer_info`;",
				Adapter::QUERY_MODE_EXECUTE
			);
			
			$response->appendVerbose($verbose, "Buyers found: " . $populatedBuyers->count());
			
			foreach($populatedBuyers->toArray() as $value)
			{
				if(isset($buyersIds[$value["buyer_id"]]))
				{
					unset($buyersIds[$value["buyer_id"]]);
				}
			}
			
			
			$response->appendVerbose($verbose, "Anonymous buyers found: " . count($buyersIds));
			
			$generator = Factory::create('sl_SI');
			
			if(!empty($buyersIds))
			{
				$response->appendVerbose($verbose, "Attempting to create buyer info");
				
				$query = "INSERT INTO `buyer_info` (`buyer_id`, `first_name`, `last_name`) VALUES ";
				
				foreach($buyersIds as $id)
				{
					
					$name = $adapter->getPlatform()->quoteValue($generator->firstName());
					$lastname = $adapter->getPlatform()->quoteValue($generator->lastName);
					$query .= "(" . $id . ", " . $name . ", " . $lastname . "),";
				}
				
				$query = substr($query, 0, -1);
				
				$adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
				
				$response->appendMessage("Buyer info updated with new values.");
			}
			else
			{
				$response->appendMessage("Nothing new to be added.");
			}
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: " . $e->getMessage());
		}
		
		return $response->getMessages();
	}
	
	/**
	 * @name ->bestSellingModelAction()
	 *
	 * @return string
	 */
	public function bestSellingModelAction()
	{
		$response = new ConsoleResponseBuilder();
		
		$request = $this->getRequest();
		$verbose = $request->getParam("v") | $request->getParam("verbose");
		$userId = $request->getParam("userId");
		$userId = $userId === (string)intval($userId)
			? intval($userId)
			: null;
		
		try
		{
			$adapter = AdapterAccess::getAdapter();
			
			$query = "";
			if($userId === null)
			{
				$response->appendVerbose($verbose, "No valid user id passed, showing data for all users");
				
				$query = "SELECT DISTINCT s1.buyer_id, s1.model_id
							FROM sales_info AS s1
							WHERE
								s1.model_id = (
									SELECT s2.model_id
									FROM sales_info AS s2
									WHERE s1.buyer_id = s2.buyer_id
									GROUP BY s2.model_id
									ORDER BY count(s2.model_id)
									DESC LIMIT 1
								);";
				
				$result = $adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
				$result->rewind();
				while($result->valid())
				{
					$row = $result->current()->getArrayCopy();
					$response->appendMessage(json_encode($row));
					$result->next();
				}
			}
			else
			{
				$response->appendVerbose($verbose, "Showing data for user " . $userId);
				
				$query = "SELECT buyer_id, model_id, count(model_id) AS times_purchased
								FROM sales_info
								WHERE buyer_id = " . $userId . "
								GROUP BY model_id
								ORDER BY times_purchased DESC
								LIMIT 1";
				
				$result = $adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
				
				$response->appendMessage(json_encode($result->current()->getArrayCopy()));
			}
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: " . $e->getMessage());
		}
		
		
		return $response->getMessages();
	}
	
	/**
	 * @name ->bestSellerInRowAction()
	 *
	 * @return string
	 */
	public function bestSellerInRowAction()
	{
		$response = new ConsoleResponseBuilder();
		$request = $this->getRequest();
		$verbose = $request->getParam("verbose") | $request->getParam("v");
		
		try
		{
			$response->appendVerbose($verbose, "Accessing DB...");
			
			$adapter = AdapterAccess::getAdapter();
			
			$query = "SELECT model_id, COUNT(model_id) AS sold, YEAR(sale_date) AS y, MONTH(sale_date) AS m
						FROM sales_info
						GROUP BY YEAR(sale_date), MONTH(sale_date), model_id DESC;";
			
			$results = $adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
			$results->rewind();
			
			$topSoldModels = array();
			
			$response->appendVerbose($verbose, "Sorting rows to array");
			while($results->valid())
			{
				$row = $results->current()->getArrayCopy();
				$ym = $row["y"] . "-" . $row["m"];
				
				if(!isset($topSoldModels[$ym]))
				{
					$topSoldModels[$ym] = array("model" => $row["model_id"], "sold" => $row["sold"]);
				}
				else
				{
					if($topSoldModels[$ym]["sold"] < $row["sold"])
					{
						$topSoldModels[$ym] = array("model" => $row["model_id"], "sold" => $row["sold"]);
					}
				}
				
				$results->next();
			}
			
			$response->appendVerbose($verbose, "Searching for bestseller in 3 months in a row");
			$mostMonths = 0;
			$currentMonths = 0;
			$model = null;
			
			foreach($topSoldModels as $month => $modelInfo)
			{
				if($model === null)
				{
					$mostMonths = 1;
					$currentMonths = 1;
					$model = $modelInfo["model"];
				}
				else
				{
					if($model === $modelInfo["model"])
					{
						$currentMonths++;
					}
					else
					{
						$modelInfo = $modelInfo["model"];
						$mostMonths = $currentMonths > $mostMonths ? $currentMonths : $mostMonths;
						$currentMonths = 1;
					}
				}
				
				if($currentMonths === 3)
				{
					break;
				}
			}
			
			if($currentMonths === 3)
			{
				$response->appendMessage("Topseller in 3 months in a row was " . $model);
			}
			else
			{
				$response->appendMessage("There was no topseller in 3 months in a row");
				$response->appendVerbose($verbose, "Most months in a row was " . $mostMonths);
			}
			
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: " . $e->getMessage());
		}
		
		return $response->getMessages();
	}
	
	/**
	 * @name ->addSaleAction()
	 *
	 * @return string
	 */
	public function addSaleAction()
	{
		$response = new ConsoleResponseBuilder();
		$request = $this->getRequest();
		
		$verbose = $request->getParam("v") | $request->getParam("verbose");
		$vehicleId = $request->getParam("VehicleID");
		$inhouseSellerID = $request->getParam("InhouseSellerID");
		$buyerId = $request->getParam("BuyerID");
		$modelID = $request->getParam("ModelID");
		$sellDate = $request->getParam("SaleDate");
		$buyDate = $request->getParam("BuyDate");
		
		try
		{
			$response->appendVerbose($verbose, "Checking parameters");
			if($vehicleId !== (string)intval($vehicleId))
			{
				throw new \Exception("VehicleID is supposed to be INT");
			}
			if($inhouseSellerID !== (string)intval($inhouseSellerID))
			{
				throw new \Exception("InhouseSellerID is supposed to be INT");
			}
			if($buyerId !== (string)intval($buyerId))
			{
				throw new \Exception("BuyerID is supposed to be INT");
			}
			if($modelID !== (string)intval($modelID))
			{
				throw new \Exception("ModelID is supposed to be INT");
			}
			if(\DateTime::createFromFormat("Y-m-d", $sellDate) === false)
			{
				throw new \Exception("SaleDate is supposed to be date with format Y-m-d");
			}
			if(\DateTime::createFromFormat("Y-m-d", $buyDate) === false)
			{
				throw new \Exception("BuyDate is supposed to be date with format Y-m-d");
			}
			
			$response->appendVerbose($verbose, "Parameters seem to be fine");
			$response->appendVerbose($verbose, "Inserting data into DB");
			
			$adapter = AdapterAccess::getAdapter();
			
			$buyDate = \DateTime::createFromFormat("Y-m-d", $buyDate);
			$sellDate = \DateTime::createFromFormat("Y-m-d", $sellDate);
			
			$query = "INSERT INTO `sales_info`
						(`vehicle_id`, `inhouse_seller_id`, `buyer_id`, `model_id`, `sale_date`, `buy_date`)
						VALUES (
						" . $adapter->getPlatform()->quoteValue(intval($vehicleId)) . ",
						" . $adapter->getPlatform()->quoteValue(intval($inhouseSellerID)) . ",
						" . $adapter->getPlatform()->quoteValue(intval($buyerId)) . ",
						" . $adapter->getPlatform()->quoteValue(intval($modelID)) . ",
						" . $adapter->getPlatform()->quoteValue($sellDate->format("Y-m-d")) . ",
						" . $adapter->getPlatform()->quoteValue($buyDate->format("Y-m-d")) . "
						)";
			
			$adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
			
			$response->appendVerbose($verbose, "Insertion complete!");
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: " . $e->getMessage());
		}
		
		return $response->getMessages();
	}
}