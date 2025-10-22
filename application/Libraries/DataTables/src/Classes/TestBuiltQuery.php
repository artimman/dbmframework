<?php
/**
 * Library: DbM DataTables PHP
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * Usage example - query preview in controller (test of the built query):
 * $dtService = $this->dataTable->withParams($dtParams);
 * 1. Typical option
 * $dtResult = $dtService->paginate($this->configDataTable);
 * 2. Option RAW
 * $sql = $this->configDataTable->getSql();
 * $maps = $this->configDataTable->getMaps();
 * $dtResult = $dtService->paginateRaw($sql, $maps);
 * 3. Show Query
 * dump($dtService->getLastBuiltQuery());
 */

declare(strict_types=1);

namespace Lib\DataTables\Src\Classes;

final class TestBuiltQuery
{
    public function __construct(
        public string $sql,
        /** @var array<string,mixed> */
        public array $params = []
    ) {
    }

    public function __toString(): string
    {
        return $this->sql . ' | PARAMS: ' . json_encode($this->params, JSON_UNESCAPED_UNICODE);
    }
}
