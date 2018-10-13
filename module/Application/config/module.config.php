<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Application\Controller\Basic1;
use Application\Controller\Basic2;
use Application\Controller\Practical;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
	'console' => [
		'router' => [
			'routes' => [
				'preskok-basic-1' => [
					'options' => [
						'route' => 'preskok syncaws3 [--verbose|-v] <directory>',
						'defaults' => [
							'controller' => Basic1::class,
							'action' => 'syncAWS3'
						]
					]
				],
				'preskok-basic-2' => [
					'options' => [
						'route' => 'preskok syncsql [--verbose|-v] <directory>',
						'defaults' => [
							'controller' => Basic2::class,
							'action' => 'syncSQL'
						]
					]
				],
				'preskok-practical-parser' => [
					'options' => [
						'route' => 'preskok practical [--verbose|-v] parser',
						'defaults' => [
							'controller' => Practical::class,
							'action' => 'parser'
						]
					]
				],
				'preskok-practical-dbcheck' => [
					'options' => [
						'route' => 'preskok practical [--verbose|-v] dbcheck',
						'defaults' => [
							'controller' => Practical::class,
							'action' => 'dbCheck'
						]
					]
				]
			]
		]
	],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
			Controller\Basic1::class => InvokableFactory::class,
			Controller\Basic2::class => InvokableFactory::class,
			Controller\Practical::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
