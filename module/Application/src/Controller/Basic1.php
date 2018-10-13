<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 11:19 AM
 */

namespace Application\Controller;


use Aws\S3\S3Client;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;
use Application\Modules\Console\ConsoleResponseBuilder;
use Application\Modules\Aws3\Aws3Communication;

class Basic1 extends AbstractActionController
{
	/** @var  boolean */
	private $verbose;
	
	
	/**
	 * @name ->syncAWS3Action()
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function syncAWS3Action()
	{
		$response = new ConsoleResponseBuilder();
		
		try
		{
			/** @ConsoleRequest $request */
			$request = $this->getRequest();
			$s3 = new Aws3Communication();
			
			$this->verbose = $request->getParam("v") | $request->getParam("verbose");
			$directory = $request->getParam("directory");
			$explodedDir = explode("/", trim($directory, "/"));
			
			$possibleBuckets = array($directory, array_pop($explodedDir));
			$bucket = null;
			$localDirectory = implode("/", $explodedDir) . "/";
			$log = array();
			
			if(file_exists($tmp = getcwd()."/data/basic1-log/log.json"))
			{
				$log = json_decode(file_get_contents($tmp), true);
			}
			
			
			if(!is_dir($directory))
			{
				throw new \Exception("Directory not found.");
			}
			
			foreach($possibleBuckets as $possibleBucket)
			{
				if($s3->getClient()->doesBucketExist($possibleBucket))
				{
					$bucket = $possibleBucket;
					$response->appendVerbose($this->verbose, "Bucket found: " . $possibleBucket);
					break;
				}
				else
				{
					$response->appendVerbose($this->verbose, "Bucket does not exist: " . $possibleBucket);
				}
			}
			
			if($bucket === null)
			{
				throw new \Exception("No valid bucket was found.");
			}
			else
			{
				$localDirectory = ($bucket == $directory) ? $localDirectory : $directory;
				$s3::setDefaultBucket($bucket);
			}
			
			$s3Files = iterator_to_array($s3->getFileList($bucket));
			foreach($s3Files as $file)
			{
				$this->updateLog($log, $file["Key"], "amazon");
				$response->appendVerbose($this->verbose, "S3 file found: " . $file["Key"]);
			}
			
			$localFiles = iterator_to_array(
				new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
				)
			);
			
			foreach($localFiles as $dirInfo)
			{
				$this->updateLog($log, substr($dirInfo->getPathname(), strlen($localDirectory)), "local");
				$response->appendVerbose($this->verbose, "Local file found: " . $dirInfo->getPathname());
			}
			
			$response->appendMessage(\json_encode($log));
			
			$this->uploadLocalFiles($localFiles, $localDirectory, $s3Files, $response, $log);
			$this->downloadRemoteFiles($s3Files, $localDirectory, $localFiles, $response, $log);
			
			file_put_contents(getcwd()."/data/basic1-log/log.json", json_encode($log));
		}
		catch(\Exception $e)
		{
			$response->appendMessage($e->getMessage());
		}
		
		return $response->getMessages();
	}
	
	/**
	 * @name ->uploadLocalFiles()
	 *
	 * @param \Iterator|\SplFileInfo[] $localFiles             list of local files
	 * @param string                   $location               local directory location
	 * @param \Iterator|array          $remoteFiles            remote files list
	 * @param ConsoleResponseBuilder   $consoleResponseBuilder response builder
	 * @param array                    $log                    log file
	 */
	private function uploadLocalFiles($localFiles, $location, $remoteFiles, &$consoleResponseBuilder, &$log = array())
	{
		$s3 = new Aws3Communication();
		foreach($localFiles as $localFile)
		{
			$relativeFileName = substr($localFile->getPathname(), strlen($location));
			
			if(isset($log[$relativeFileName]) && $log[$relativeFileName]["type"] === "synced")
			{
				$consoleResponseBuilder->appendVerbose(
					$this->verbose,
					"File detected as synced: " . $relativeFileName
				);
				continue;
			}
			
			$synced = false;
			foreach($remoteFiles as $file)
			{
				if($relativeFileName == $file["Key"])
				{
					$synced = true;
					break;
				}
			}
			
			if($synced)
			{
				$consoleResponseBuilder->appendVerbose(
					$this->verbose,
					"Local file found on remote storage: " . $relativeFileName
				);
			}
			else
			{
				$consoleResponseBuilder->appendVerbose(
					$this->verbose,
					"Local file has to be uploaded: " . $relativeFileName
				);
				
				$s3->uploadFile($s3::getDefaultBucket(), $localFile->getPathname(), $relativeFileName);
				$log[$relativeFileName] = array("date" => microtime(true), "type" => "synced");
			}
		}
	}
	
	/**
	 * @name ->downloadRemoteFiles()
	 *
	 * @param \Iterator|array          $remoteFiles            remote files
	 * @param string                   $location               local directory
	 * @param \Iterator|\SplFileInfo[] $localFiles             locally stored files
	 * @param ConsoleResponseBuilder   $consoleResponseBuilder response builder
	 * @param array                    $log                    log file
	 */
	private function downloadRemoteFiles(
		$remoteFiles,
		$location,
		$localFiles,
		&$consoleResponseBuilder,
		&$log = array()
	)
	{
		$s3 = new Aws3Communication();
		foreach($remoteFiles as $remoteFile)
		{
			if(substr($remoteFile["Key"], -1) === "/")
			{
				$consoleResponseBuilder->appendVerbose(
					$this->verbose,
					"Remote file is directory, ignoring: " . $remoteFile["Key"]
				);
				continue;
			}
			
			if(isset($log[$remoteFile["Key"]]) && $log[$remoteFile["Key"]]["type"] === "synced")
			{
				$consoleResponseBuilder->appendVerbose(
					$this->verbose,
					"File detected as synced: " . $remoteFile["Key"]
				);
				continue;
			}
			
			$synced = false;
			foreach($localFiles as $localFile)
			{
				$relativeFileName = substr($localFile->getPathname(), strlen($location));
				if($remoteFile["Key"] == $relativeFileName)
				{
					$synced = true;
					break;
				}
			}
			
			if($synced)
			{
				$consoleResponseBuilder->appendVerbose(
					$this->verbose,
					"Remote file found on local storage: " . $remoteFile["Key"]
				);
			}
			else
			{
				$consoleResponseBuilder->appendVerbose(
					$this->verbose,
					"Remote file has to be downloaded: " . $remoteFile["Key"]
				);
				
				$s3->downloadFile($s3::getDefaultBucket(), $remoteFile["Key"], $location . $remoteFile["Key"]);
				$log[$remoteFile["Key"]] = array("date" => microtime(true), "type" => "synced");
			}
		}
	}
	
	/**
	 * @name ->updateLog()
	 *
	 * @param array  $log  log file
	 * @param string $file filename
	 * @param string $type amazon or local
	 */
	private function updateLog(&$log, $file, $type)
	{
		if(!isset($log[$file]))
		{
			$log[$file] = array("date" => microtime(true), "type" => $type);
		}
		else if($log[$file][$type] !== $type)
		{
			$log[$file] = array("date" => microtime(true), "type" => "synced");
		}
	}
}