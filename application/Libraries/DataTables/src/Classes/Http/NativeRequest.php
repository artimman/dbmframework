<?php
/**
 * Library: DbM DataTables PHP
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Lib\DataTables\Src\Classes\Http;

use Lib\DataTables\Src\Interfaces\Http\RequestInterface;

class NativeRequest implements RequestInterface
{
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function getQueryParams(): array
    {
        return $_GET;
    }

    public function getServerParams(): array
    {
        return $_SERVER;
    }
}
