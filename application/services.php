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
 * INFO! File related to DependecyContainer()
 */

declare(strict_types=1);

use App\Controller\IndexController;
use App\Service\IndexService;
use Dbm\Classes\Database;
use Dbm\Classes\DependencyContainer;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Logs\Logger;
use Dbm\Interfaces\DatabaseInterface;

//-INSTALL_POINT_ADD_USE

return function (DependencyContainer $container) {
    // Registers DatabaseInterface as a dependency
    $container->set(DatabaseInterface::class, function () {
        return isConfigDatabase() ? new Database() : null;
    });

    // Registers Request as a dependency
    $container->set(Request::class, function () {
        return new Request();
    });

    // Registers Logger as a dependency
    $container->set(Logger::class, function () {
        return new Logger();
    });

    // Registers IndexController and creates it manually, passing services (dependencies)
    $container->set(IndexController::class, function (DependencyContainer $container) {
        return new IndexController(
            $container->get(IndexService::class), // Use when service is required
            $container->has(DatabaseInterface::class) ? $container->get(DatabaseInterface::class) : null, // Use when service is optional
        );
    });

    // Registers IndexService
    $container->set(IndexService::class, function () {
        return new IndexService();
    });

    //-INSTALL_POINT_ADD_DEPENDENCIES
};
