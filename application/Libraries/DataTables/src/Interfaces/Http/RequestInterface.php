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

namespace Lib\DataTables\Src\Interfaces\Http;

interface RequestInterface
{
    public function getQuery(string $key, mixed $default = null): mixed;

    public function getQueryParams(): array;

    public function getServerParams(): array;
}
