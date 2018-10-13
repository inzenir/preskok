<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 8:47 PM
 */

namespace Application\Controller;


use Application\Modules\Console\ConsoleResponseBuilder;
use Application\Modules\Practical\DatabaseCheck;
use Application\Modules\Practical\Parser;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * Class Practical
 *
 * @package Application\Controller
 * @author  : inzenir
 */
class Practical extends AbstractActionController
{
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
			$response->appendMessage("EXCEPTION: ".$e->getMessage());
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
				$dbCheck->createDB();
				$response->appendMessage("Table created.");
			}
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: ".$e->getMessage());
		}
		
		return $response->getMessages();
	}
}