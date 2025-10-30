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
 * DOCUMENTATION: Examples can be found in the README documentation -> Routing
 */

declare(strict_types=1);

use App\Controller\IndexController;
use Dbm\Classes\Router;

//-INSTALL_POINT_ADD_USE

return function (Router $router): void {
    //-INSTALL_POINT_ADD_VALUES

    // Index routes
    $router->get('/', [IndexController::class, 'index'], 'index');
    $router->get('/start', [IndexController::class, 'start'], 'start');
    $router->get('/installer', [IndexController::class, 'installer'], 'installer');

    //-INSTALL_POINT_ADD_ROUTES
};
