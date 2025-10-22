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

namespace Lib\DataTables\Src\Interfaces;

use Lib\DataTables\Src\Classes\TestBuiltQuery;

interface RepositoryInterface
{
    /**
    * Returns rows for given constraints.
    * @param array<string, mixed> $filters
    * @param string $sortKey Whitelisted logical sort key
    * @param 'ASC'|'DESC' $dir
    * @return array<int, array<string, mixed>|object>
    */
    public function list(int $limit, int $offset, array $filters, string $sortKey, string $dir): array;

    /**
    * Returns total rows for given filters (without limit/offset).
    * @param array<string, mixed> $filters
    */
    public function count(array $filters): int;

    /**
     * For testing purposes - download the most recently created query
     */
    public function getLastBuiltQuery(): ?TestBuiltQuery;
}
