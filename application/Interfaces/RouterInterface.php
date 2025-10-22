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

namespace Dbm\Interfaces;

interface RouterInterface
{
    public function dispatch(string $uri): void;

    public function getRoutes(): array;

    public function get(string $route, array $handler, ?string $name = null): void;

    public function post(string $route, array $handler, ?string $name = null): void;

    public function put(string $route, array $handler, ?string $name = null): void;

    public function delete(string $route, array $handler, ?string $name = null): void;
}
