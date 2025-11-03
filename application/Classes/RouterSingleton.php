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

namespace Dbm\Classes;

use Dbm\Interfaces\DatabaseInterface;

final class RouterSingleton
{
    private static ?Router $instance = null;

    public static function getInstance(?DatabaseInterface $database = null): Router
    {
        if (self::$instance === null) {
            self::$instance = new Router($database);
        }
        return self::$instance;
    }

    public static function setInstance(Router $router): void
    {
        self::$instance = $router;
    }
}
