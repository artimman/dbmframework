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

/**
 * Common database abstraction layer for DataTables library.
 *
 * This interface defines a minimal contract that must be implemented
 * by any database adapter to allow the library to fetch data
 * in a consistent way, regardless of the underlying system.
 *
 * Typical adapters will wrap an application-specific Database class
 * and adapt its methods to this contract.
 */
interface DatabaseInterface
{
    /**
     * Execute a SQL query and return all rows as an array.
     *
     * @param string $sql The SQL query to execute.
     * @param array $params Optional bound parameters for prepared statements.
     *
     * @return array A list of rows (each row as associative array).
     */
    public function query(string $sql, array $params = []): array;

    /**
     * Execute a SQL query and fetch the first row.
     *
     * @param string $sql The SQL query to execute.
     * @param array $params Optional bound parameters.
     *
     * @return array|null The first row as associative array, or null if no result.
     */
    public function fetch(string $sql, array $params = []): ?array;

    /**
     * Execute a SQL query and fetch all rows.
     *
     * @param string $sql The SQL query to execute.
     * @param array $params Optional bound parameters.
     *
     * @return array A list of rows (each row as associative array).
     */
    public function fetchAll(string $sql, array $params = []): array;
}
