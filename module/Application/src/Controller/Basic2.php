<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 6:21 PM
 */

namespace Application\Controller;


use Application\Modules\Console\ConsoleResponseBuilder;
use Zend\Mvc\Controller\AbstractActionController;

class Basic2 extends AbstractActionController
{
	const DATE_REGEX = '/([\d]{4})([\d]{2})([\d]{2})/';
	
	/** @var bool */
	private $verbose = false;
	
	/**
	 * @name ->syncSQLAction()
	 *
	 * @return string
	 */
	public function syncSQLAction()
	{
		$response = new ConsoleResponseBuilder();
		
		try
		{
			$request = $this->getRequest();
			$this->verbose = $request->getParam("v") | $request->getParam("verbose");
			$directory = rtrim($request->getParam("directory"), "/");
			$currentDateString = (new \DateTime("now"))->format("Ymd");
			
			if(!is_dir($directory . "/applied"))
			{
				mkdir($directory . "/applied");
				$response->appendVerbose($this->verbose, "Applied directory was missing and was created.");
			}
			
			if(!is_dir($directory . "/applied/" . $currentDateString))
			{
				mkdir($directory . "/applied/" . $currentDateString);
				$response->appendVerbose($this->verbose, "Datetime directory was missing and was created.");
			}
			
			$iterator = new \DirectoryIterator($directory);
			$fileList = array();
			foreach($iterator as $file)
			{
				if($file->isDot())
				{
					continue;
				}
				
				$result = array();
				$regex = preg_match(self::DATE_REGEX, $file->getFilename(), $result);
				
				if(strtolower($file->getExtension()) !== "sql")
				{
					$response->appendVerbose(
						$this->verbose,
						"File is not a valid SQL file: " . $file->getFilename()
					);
				}
				else if($regex !== 1)
				{
					$response->appendVerbose(
						$this->verbose,
						"File does not contain a valid date: " . $file->getFilename()
					);
				}
				else
				{
					$dateInt = $result[1] * 10000 + $result[2] * 100 + $result[3];
					$fileList[$dateInt] = $file->getPathname();
					
					$response->appendVerbose(
						$this->verbose,
						"File added to processing list: " . $file->getFilename()
					);
				}
			}
		}
		catch(\Exception $e)
		{
			$response->appendMessage($e->getMessage());
		}
		
		return $response->getMessages();
	}
}