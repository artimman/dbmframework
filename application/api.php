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
 * DOCUMENTATION: Examples can be found in the README documentation
 * -> Application Programming Interface (API)
 */

declare(strict_types=1);

use App\Controller\Api\ExampleApiController;
use Dbm\Classes\Router;

return function (Router $router): void {
    $router->group('/api', function (Router $router) {
        // $router->get('/example', [ExampleApiController::class, 'list'], 'api_example_list');
        // $router->get('/example/{id}', [ExampleApiController::class, 'get'], 'api_example_get');
        // $router->post('/example', [ExampleApiController::class, 'create'], 'api_example_create');
        // $router->put('/example/{id}', [ExampleApiController::class, 'update'], 'api_example_update');
        // $router->delete('/example/{id}', [ExampleApiController::class, 'delete'], 'api_example_delete');
    });
};
