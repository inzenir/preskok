<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 6:21 PM
 */

namespace Application\Controller;


use Application\Modules\Console\ConsoleResponseBuilder;
use Application\Modules\DB\AdapterAccess;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Adapter\Adapter;

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
			
			$targetDirectory = $this->generateDirectories($directory, $response);
			$sqlFiles = $this->getSqlFiles($directory, $response);
			
			$adapter =& AdapterAccess::getAdapter();
			
			try
			{
				$mergedQuery = $this->mergeFiles($sqlFiles, $response);
				$adapter->getDriver()->getConnection()->beginTransaction();
				$adapter->query($mergedQuery, Adapter::QUERY_MODE_EXECUTE);
				$adapter->getDriver()->getConnection()->commit();
				
				$this->migrateFiles($sqlFiles, $targetDirectory, $response);
			}
			catch(\Exception $dbException)
			{
				$adapter->getDriver()->getConnection()->rollback();
				throw $dbException;
			}
			
			AdapterAccess::getAdapter()->query("SHOW DATABASES;", Adapter::QUERY_MODE_EXECUTE);
			
			$response->appendMessage("success");
		}
		catch(\Exception $e)
		{
			$response->appendMessage("EXCEPTION: " . $e->getMessage());
		}
		
		return $response->getMessages();
	}
	
	/**
	 * @name ->generateDirectories()
	 *
	 *       Creates missing dirs and returns the destination dir
	 *
	 * @param string                 $directory
	 * @param ConsoleResponseBuilder $response
	 *
	 * @return string
	 */
	private function generateDirectories($directory, &$response)
	{
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
		
		return $directory . "/applied/" . $currentDateString;
	}
	
	/**
	 * @name ->getSqlFiles()
	 *
	 * @param string                 $directory
	 * @param ConsoleResponseBuilder $response
	 *
	 * @return array Array <dateInt> => <fileName>
	 */
	private function getSqlFiles($directory, &$response)
	{
		$fileList = array();
		
		$iterator = new \DirectoryIterator($directory);
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
		
		return $fileList;
	}
	
	/**
	 * @name ->mergeFiles()
	 *
	 * @param string[]               $filesList
	 * @param ConsoleResponseBuilder $response
	 *
	 * @return string
	 */
	private function mergeFiles($filesList, &$response)
	{
		$merged = "";
		
		foreach($filesList as $file)
		{
			$merged .= file_get_contents($file);
			$response->appendVerbose($this->verbose, "File appended: " . $file);
		}
		
		return $merged;
	}
	
	/**
	 * @name ->migrateFiles()
	 *
	 *       Migrates files to destination directory
	 *
	 * @param string[]               $filesList
	 * @param string                 $destination
	 * @param ConsoleResponseBuilder $response
	 */
	private function migrateFiles($filesList, $destination, &$response)
	{
		foreach($filesList as $file)
		{
			rename($file, $destination . "/" . basename($file));
			$response->appendVerbose($this->verbose, "File migrated to" . $destination . "/" . basename($file));
		}
	}
}