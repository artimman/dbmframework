<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * DOCUMENTATION: Examples can be found in the README documentation -> Middleware
 */

declare(strict_types=1);

use Dbm\Classes\Router;
use Dbm\Classes\Log\Logger;
use Dbm\Middleware\ApiAuthMiddleware;
use Dbm\Middleware\CorsMiddleware;
use Dbm\Middleware\RequestLoggerMiddleware;

return function (Router $router): void {
    $logger = new Logger();

    // Global
    $router->addMiddleware(new CorsMiddleware());

    // Test request time in ms
    // $router->addMiddleware(new RequestLoggerMiddleware($logger));

    // API-only
    $router->addMiddleware(new ApiAuthMiddleware(), '/api');
};
