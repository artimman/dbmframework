<?php
/**
 * Library: DbM Search Engine
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Lib\Search\Src\Traits;

trait SearchResultMapperTrait
{
    protected function mapRows(array $rows, callable $mapper): array
    {
        return array_map($mapper, $rows);
    }
}
