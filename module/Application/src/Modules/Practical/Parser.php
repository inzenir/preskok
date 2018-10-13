<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 9:02 PM
 */

namespace Application\Modules\Practical;


use Application\Modules\Console\ConsoleResponseBuilder;

class Parser implements AcceptsConsoleResponseBuilderInterface
{
	const TEST_LOCATION = "https://admin.b2b-carmarket.com//test/project";
	
	/** @var  string */
	private $location = "";
	
	/** @var string  */
	private $downloadedData = "";
	
	/** @var null|ConsoleResponseBuilder */
	private $consoleResponseBuilder = null;
	
	/**
	 * Parser constructor.
	 *
	 * @param null|string $location
	 */
	public function __construct($location = null)
	{
		$this->location = ($location === null ? self::TEST_LOCATION : $location);
	}
	
	/**
	 * @name ->getDataAsArray()
	 *
	 * @return array
	 */
	public function getDataAsArray()
	{
		$response = array();
		
		$this->downloadData();
		$response = $this->parseData();
		
		return $response;
	}
	
	/**
	 * @name ->downloadData()
	 *
	 */
	private function downloadData()
	{
		$this->consoleResponseBuilder->appendVerboseDefault("Attempting to download data.");
		$this->downloadedData = file_get_contents($this->location);
		$this->consoleResponseBuilder->appendVerboseDefault("Data download completed.");
	}
	
	/**
	 * @name ->parseData()
	 *
	 * @return array
	 */
	private function parseData()
	{
		$this->consoleResponseBuilder->appendVerboseDefault("Beginning to parse data.");
		
		$header = array();
		$parsedData = array();
		
		$data = explode("\n<br>", $this->downloadedData);
		
		foreach(explode(",", array_shift($data)) as $key => $value)
		{
			$header[$key] = $value;
		}
		
		foreach($data as $i => $row)
		{
			$rowData = array();
			foreach(explode(",", $row) as $key => $value)
			{
				$rowData[$header[$key]] = $value;
			}
			
			$parsedData[] = $rowData;
		}
		$this->consoleResponseBuilder->appendVerboseDefault("Data parsing complete");
		
		return $parsedData;
	}
	
	/**
	 * @inheritdoc
	 */
	public function setConsoleResponseBuilder(&$consoleResponseBuilder)
	{
		$this->consoleResponseBuilder =& $consoleResponseBuilder;
	}
	
}