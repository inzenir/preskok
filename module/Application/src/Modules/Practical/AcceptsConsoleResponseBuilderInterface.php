<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 10/13/2018
 * Time: 9:30 PM
 */

namespace Application\Modules\Practical;


use Application\Modules\Console\ConsoleResponseBuilder;

interface AcceptsConsoleResponseBuilderInterface
{
	/**
	 * @name ->setConsoleResponseBuilder()
	 *
	 * @param ConsoleResponseBuilder $consoleResponseBuilder
	 *
	 * @return void
	 */
	public function setConsoleResponseBuilder(&$consoleResponseBuilder);
}