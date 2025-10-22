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

use App\Controller\Api\PanelBlogApiController;
use Dbm\Classes\Router;

return function (Router $router): void {
    $router->group('/api', function (Router $router) {
        $router->get('/articles', [PanelBlogApiController::class, 'list'], 'api_articles_list');
        $router->get('/articles/{id}', [PanelBlogApiController::class, 'get'], 'api_articles_get');
        $router->post('/articles', [PanelBlogApiController::class, 'create'], 'api_articles_create');
        $router->put('/articles/{id}', [PanelBlogApiController::class, 'update'], 'api_articles_update');
        $router->delete('/articles/{id}', [PanelBlogApiController::class, 'delete'], 'api_articles_delete');
    });
};
