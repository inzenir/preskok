<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 1:08 PM
 */

namespace Application\Modules\Aws3;


use Aws\S3\S3Client;

/**
 * Class Aws3Communication
 *
 * @package Application\Modules\Aws3
 * @author  : inzenir
 */
class Aws3Communication
{
	/** @var string */
	private static $access_key = "";	// access key goes here
	
	/** @var string */
	private static $access_secret = "";	// access secret goes here
	
	/** @var  S3Client */
	private static $client = null;
	
	/** @var string */
	private static $defaultBucket = "";
	
	/**
	 * Aws3Communication constructor.
	 *
	 * @param string $region
	 * @param string $version
	 */
	public function __construct($region = "eu-west-3", $version = "latest")
	{
		if(self::$client === null)
		{
			$this->createClient($region, $version);
		}
	}
	
	/**
	 * @name ->getFileList()
	 *
	 * @param string $bucket
	 *
	 * @return \Iterator
	 */
	public function getFileList($bucket)
	{
		$iterator = $this->getClient()->getIterator(
			"ListObjects",
			array(
				'Bucket' => $bucket
			)
		);
		
		return $iterator;
	}
	
	/**
	 * @name ->uploadFile()
	 *
	 * @param string $bucket
	 * @param string $filePath
	 * @param string $fileName
	 *
	 * @return \Aws\Result
	 */
	public function uploadFile($bucket, $filePath, $fileName)
	{
		return $this->getClient()->putObject(
			array(
				'Bucket' => $bucket,
				'Key'    => $fileName,
				'Body'   => fopen($filePath, 'r')
			)
		);
	}
	
	/**
	 * @name ->downloadFile()
	 *
	 * @param string $bucket
	 * @param string $key
	 * @param string $filePath
	 */
	public function downloadFile($bucket, $key, $filePath)
	{
		$object = $this->getClient()->getObject(
			array(
				'Bucket' => $bucket,
				'Key'    => $key
			)
		);
		
		$dirName = dirname($filePath);
		if(!is_dir($dirName)){
			mkdir($dirName);
		}
		
		$file = fopen($filePath, 'w');
		fwrite($file, $object["Body"]);
		fclose($file);
	}
	
	/**
	 * @name ->createClient()
	 *
	 * @param string $region
	 * @param string $version
	 */
	public function createClient($region = "eu-west-3", $version = "latest")
	{
		self::$client = new S3Client(
			array(
				'region'      => $region,
				'version'     => $version,
				'credentials' => array(
					'key'    => self::$access_key,
					'secret' => self::$access_secret
				)
			)
		);
	}
	
	/**
	 * @name ->getClient()
	 *
	 * @return S3Client
	 */
	public function getClient()
	{
		if(self::$client === null)
		{
			$this->createClient();
		}
		
		return self::$client;
	}
	
	/**
	 * @return string
	 */
	public static function getDefaultBucket()
	{
		return self::$defaultBucket;
	}
	
	/**
	 * @param string $defaultBucket
	 */
	public static function setDefaultBucket($defaultBucket)
	{
		self::$defaultBucket = $defaultBucket;
	}
}