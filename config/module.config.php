<?php
/**
 * Stakhanovist
 *
 * @link        https://github.com/stakhanovist/zf2-module
 * @copyright   Copyright (c) 2015, Stakhanovist
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
return [
    'service_manager' => [
        'abstract_factories' => [
            'Stakhanovist\Queue\Service\QueueAbstractServiceFactory',
            'Stakhanovist\Queue\Service\QueueAdapterAbstractServiceFactory',
        ],
        'factories'  => [
        ],

        'shared' => [
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'queue' => 'Stakhanovist\Controller\Plugin\Queue',
        ],
    ],

    'controllers' => [
        'invokables' => [
            'Stakhanovist\Worker\ConsoleWorkerController' => 'Stakhanovist\Worker\ConsoleWorkerController',
        ],
    ],

    'console' => [
        'router' => [
            'routes' => [
                'stakhanovist-console-worker-process' => [
                    'options' => [
                        'route'     => 'stakhanovist process [--message=]',
                        'defaults'  => [
                            'controller'    => 'Stakhanovist\Worker\ConsoleWorkerController',
                            'action'        => 'process',
                        ],
                    ]
                ],
                'stakhanovist-console-worker-send' => [
                    'options' => [
                        'route'     => 'stakhanovist send --queue= [--message=] [--receiveParameters=]',
                        'defaults'  => [
                            'controller'    => 'Stakhanovist\Worker\ConsoleWorkerController',
                            'action'        => 'send',
                        ],
                    ]
                ],
                'stakhanovist-console-worker-receive' => [
                    'options' => [
                        'route'     => 'stakhanovist receive --queue= [--maxMessages=] [--receiveParameters=]',
                        'defaults'  => [
                            'controller'    => 'Stakhanovist\Worker\ConsoleWorkerController',
                            'action'        => 'receive',
                        ],
                    ]
                ],
                'stakhanovist-console-worker-await' => [
                    'options' => [
                        'route'     => 'stakhanovist await --queue= [--receiveParameters=]',
                        'defaults'  => [
                            'controller'    => 'Stakhanovist\Worker\ConsoleWorkerController',
                            'action'        => 'await',
                        ],
                    ]
                ],
            ],
        ],
    ],
];
