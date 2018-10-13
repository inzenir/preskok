<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return [
	'db' => array(
		'driver'         => 'Pdo',
		'dsn'            => 'mysql:dbname=preskok;host=localhost',
		'username' => 'preskok',	// da ne sharamo credentialov bi bilo bolje
		'password' => 'preskok123', // imeti v local configu, vendar je OK za potrebe testa
		'driver_options' => array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
		),
	),
	'service_manager' => array(
		'factories' => array(
			'Zend\Db\Adapter\Adapter'
			=> 'Zend\Db\Adapter\AdapterServiceFactory',
		),
	),
];
