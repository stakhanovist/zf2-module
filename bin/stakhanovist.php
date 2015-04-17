<?php
/**
 * Stakhanovist
 *
 * @link        https://github.com/stakhanovist/zf2-module
 * @copyright   Copyright (c) 2015, Stakhanovist
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

$basePath = getcwd();

if ($basePath === __DIR__) {
    chdir(dirname(__DIR__));
    $basePath = getcwd();
}

// load autoloader
if (file_exists("$basePath/vendor/autoload.php")) {
    require_once "$basePath/vendor/autoload.php";
} elseif (file_exists("$basePath/init_autoload.php")) {
    require_once "$basePath/init_autoload.php";
} elseif (\Phar::running()) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    echo 'Error: Cannot find the autoloader.' . PHP_EOL;
    exit(2);
}

if (file_exists("$basePath/config/application.config.php")) {
    $appConfig = require "$basePath/config/application.config.php";
    if (!isset($appConfig['modules']['Stakhanovist'])) {
        $appConfig['modules'][] = 'Stakhanovist';
        $appConfig['module_listener_options']['module_paths']['Stakhanovist'] = dirname(__DIR__);
    }
} else {
    // Used as standalone CLI tool
    $appConfig = [
        'modules' => [
            'Stakhanovist',
        ],
        'module_listener_options' => [
            'config_glob_paths'    => [
                'config/autoload/{,*.}{global,local}.php',
            ],
            'module_paths' => [
                '.',
                './vendor',
            ],
        ],
        'view_manager' => [
            'display_not_found_reason' => false,
        ]
    ];
}

Zend\Mvc\Application::init($appConfig)->run();