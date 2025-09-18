<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

use App\Controller\IndexController;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Router;

//-INSTALL_POINT_ADD_USE

return function (Router $router): void {
    $request = new Request();
    //-INSTALL_POINT_ADD_VALUES

    // Index routes
    $router->addRoute('/', [IndexController::class, 'index'], 'index');
    $router->addRoute('/start', [IndexController::class, 'start'], 'start');
    $router->addRoute('/installer', [IndexController::class, 'installer'], 'installer');
    //-INSTALL_POINT_ADD_ROUTES

    // Dispatch current request URI
    $uri = $request->getServerParams()['REQUEST_URI'] ?? '/';
    $router->dispatch($uri);
};
