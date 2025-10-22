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

use Dbm\Classes\DependencyContainer;
use Psr\Http\Message\ResponseInterface;

interface BaseApiInterface
{
    public function getSession(string $sessionName): mixed;

    public function getDatabase(): ?DatabaseInterface;

    public function getDIContainer(): DependencyContainer;

    public function jsonResponse(
        array|string|int|float|bool|null $data,
        int $status = 200,
        array $headers = []
    ): ResponseInterface;

    public function successResponse(array $data = [], int $status = 200): ResponseInterface;

    public function errorResponse(string $message, int $status = 500): ResponseInterface;

    public static function error(string $message, int $status = 500): ResponseInterface;
}
