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

namespace Lib\DataTables\Src\Classes;

final class DataTableResult
{
    /** @var array<int, array<string, mixed>|object> */
    public array $records;
    /** @var array<string, int|string> */
    public array $sider;

    /**
    * @param array<int, array<string, mixed>|object> $records
    * @param int $total
    */
    public function __construct(array $records, int $total, DataTableParams $params)
    {
        $pages = (int) ceil($total / max(1, $params->perPage));
        $this->records = $records;
        $this->sider = [
            'page' => $params->page,
            'perPage' => $params->perPage,
            'total' => $total,
            'pages' => $pages,
            'sort' => $params->sort,
            'dir' => $params->dir,
        ];
    }
}
