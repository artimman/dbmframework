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

use PDOStatement;

interface DatabaseInterface
{
    public function querySql(string $query, string $fetch = 'assoc'): PDOStatement;

    public function queryExecute(string $query, ?array $params = [], bool $reference = false): bool;

    public function multiQueryExecute(string $sql): bool;

    public function rowCount(): int;

    public function fetch(string $fetch = 'assoc'): array;

    public function fetchAll(string $fetch = 'assoc'): array;

    public function fetchObject(): object;

    public function fetchAllObject(): array;

    public function fetchColumn(): mixed;

    public function getLastInsertId(): ?string;

    public function debugDumpParams(): ?string;

    public function getLastError(): string;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    public function close(): void;

    public function buildInsertQuery(array $data, ?string $table = null): array;

    public function buildUpdateQuery(array $data, ?string $table = null, ?string $condition = null): array;

    public function importSqlFile(string $filePath): bool;
}
