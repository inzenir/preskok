<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 12:45 PM
 */

namespace Application\Modules\Console;


class ConsoleResponseBuilder
{
	/** @var string */
	private $response = "";
	
	/**
	 * ConsoleResponseBuilder constructor.
	 *
	 * @param string $message Default response message
	 */
	public function __construct($message = "")
	{
		$this->response = $message;
	}
	
	/**
	 * @name ->appendMessage()
	 *
	 * @param $message
	 */
	public function appendMessage($message)
	{
		$this->response .= $message.PHP_EOL;
	}
	
	/**
	 * @name ->appendVerbose()
	 *
	 * @param bool   $verbose
	 * @param string $message
	 */
	public function appendVerbose($verbose, $message)
	{
		if($verbose)
		{
			$this->appendMessage($message);
		}
	}
	
	/**
	 * @name ->deleteMessages()
	 *
	 */
	public function deleteMessages()
	{
		$this->response = "";
	}
	
	/**
	 * @name ->getMessages()
	 *
	 * @return string
	 */
	public function getMessages()
	{
		return $this->response;
	}
}