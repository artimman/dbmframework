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
 * Warto używać w DI, CLI / cronach, duże API, dzięki temu mamy jedno połączenie per proces PHP.
 * Przykład:
 * DI) $database = DatabaseSingleton::getInstance();
 * $controller = new ExampleController($database);
 * API) return function (Router $router): void {
 *     $router->group('/api', function (Router $router) {
 *         $database = DatabaseSingleton::getInstance();
 *         $router->get('/example', [new ExampleController($database), 'list'], 'api_example_list');
 *     });
 * };
 */

declare(strict_types=1);

namespace Dbm\Classes;

final class DatabaseSingleton
{
    private static ?Database $instance = null;

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public static function close(): void
    {
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
