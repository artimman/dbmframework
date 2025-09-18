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

namespace Dbm\Classes;

use Dbm\Classes\ExceptionHandler;
use Dbm\Interfaces\DatabaseInterface;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

class Database implements DatabaseInterface
{
    private $connect;
    private $statement;

    public function __construct(
        ?string $dbHost = null,
        ?string $dbPort = '3306',
        ?string $dbName = null,
        ?string $dbUser = null,
        ?string $dbPassword = null,
        ?string $dbCharset = 'utf8mb4'
    ) {
        $dbHost = !empty(getenv('DB_HOST')) ? getenv('DB_HOST') : $dbHost;
        $dbPort = !empty(getenv('DB_PORT')) ? getenv('DB_PORT') : $dbPort;
        $dbName = !empty(getenv('DB_NAME')) ? getenv('DB_NAME') : $dbName;
        $dbUser = !empty(getenv('DB_USER')) ? getenv('DB_USER') : $dbUser;
        $dbPassword = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : $dbPassword;
        $dbCharset = !empty(getenv('DB_CHARSET')) ? getenv('DB_CHARSET') : $dbCharset;

        try {
            $dbDSN = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
            $dbOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ];

            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) { // SET NAMES - optional for the latest MySQL versions
                $dbOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . $dbCharset;
            }

            $this->connect = new PDO($dbDSN, $dbUser, $dbPassword, $dbOptions);
        } catch (PDOException $e) {
            throw new ExceptionHandler("Database connection error: " . $e->getMessage(), 500, $e);
        }
    }

    public function querySql(string $query, string $fetch = 'assoc'): PDOStatement
    {
        try {
            if ($fetch == 'assoc') {
                return $this->connect->query($query, PDO::FETCH_ASSOC);
            }

            return $this->connect->query($query);
        } catch (PDOException $e) {
            throw new ExceptionHandler("SQL query error: " . $e->getMessage(), 500, $e);
        }
    }

    public function queryExecute(string $query, ?array $params = [], bool $reference = false): bool
    {
        try {
            $this->statement = $this->connect->prepare($query);

            if (empty($params)) {
                return $this->statement->execute();
            }

            $first = array_key_first($params);

            if (!is_string($first)) {
                return $this->statement->execute($params);
            }

            foreach ($params as $key => &$value) {
                if (is_int($value)) {
                    $type = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                    $type = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $type = PDO::PARAM_NULL;
                } else {
                    $type = PDO::PARAM_STR;
                }

                if ($type) {
                    if (!$reference) {
                        // TODO! Check insert, update null item!? $this->statement->bindValue(':' . $key, $value, $type);
                        $this->statement->bindValue($key, $value, $type);
                    } else {
                        // TODO! $this->statement->bindParam(':' . $key, $value, $type);
                        $this->statement->bindParam($key, $value, $type);
                    }
                } else {
                    throw new ExceptionHandler("Bind params error in queryExecute().", 500);
                }
            }

            return $this->statement->execute();
        } catch (PDOException $e) {
            throw new ExceptionHandler("SQL query execute error: " . $e->getMessage(), 500, $e);
        }
    }

    public function multiQueryExecute(string $sql): bool
    {
        try {
            if (!$this->connect->inTransaction()) {
                $this->connect->beginTransaction();
            }

            $queries = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($queries as $query) {
                if (!empty($query)) {
                    $this->connect->exec($query);
                }
            }

            if ($this->connect->inTransaction()) {
                $this->connect->commit();
            }

            return true;
        } catch (PDOException $e) {
            if ($this->connect->inTransaction()) {
                $this->connect->rollBack();
            }

            throw new ExceptionHandler("Multiple query execution error: " . $e->getMessage(), 500, $e);
        }
    }

    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    public function fetch(string $fetch = 'assoc'): array
    {
        if ($fetch == 'assoc') {
            return $this->statement->fetch(PDO::FETCH_ASSOC);
        }

        return $this->statement->fetch();
    }

    public function fetchAll(string $fetch = 'assoc'): array
    {
        if ($fetch == 'assoc') {
            return $this->statement->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->statement->fetchAll();
    }

    public function fetchObject(): object
    {
        return $this->statement->fetch(PDO::FETCH_OBJ);
    }

    public function fetchAllObject(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_OBJ);
    }

    public function fetchColumn(): mixed
    {
        return $this->statement->fetchColumn();
    }

    public function getLastInsertId(): ?string
    {
        return $this->connect->lastInsertId();
    }

    public function debugDumpParams(): ?string
    {
        return $this->statement->debugDumpParams();
    }

    public function getLastError(): string
    {
        $errorInfo = $this->connect->errorInfo();
        return $errorInfo[2] ?? 'SQL No Error!';
    }

    public function beginTransaction(): void
    {
        if (!$this->connect->inTransaction()) {
            $this->connect->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->connect->inTransaction()) {
            $this->connect->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->connect->inTransaction()) {
            $this->connect->rollBack();
        }
    }

    /**
     * Method for building an INSERT Query
     *
     * How to use - full query with optional parameters
     * [$filteredQuery, $filteredData] = $this->database->buildInsertQuery($data, 'dbm_invoice');
     * $this->database->queryExecute($filteredQuery, $filteredData);
     * - or basic usage
     * [$columns, $placeholders, $filteredData] = $this->database->buildInsertQuery($data);
     * $filteredQuery = "INSERT INTO table_name ($columns) VALUES ($placeholders)";
     * $this->database->queryExecute($filteredQuery, $filteredData);
     */
    public function buildInsertQuery(array $data, ?string $table = null): array
    {
        $filteredData = array_filter($data, function ($value) {
            return !is_null($value);
        });

        $columns = implode(", ", array_keys($filteredData));
        $placeholders = ':' . implode(", :", array_keys($filteredData));

        // Jeśli podano $table, budujemy pełne zapytanie
        if ($table) {
            $filteredQuery = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            return [$filteredQuery, $filteredData];
        }

        // Jeśli nie podano $table, zwracamy tylko kolumny i wartości
        return [$columns, $placeholders, $filteredData];
    }

    /**
     * Method for building an UPDATE Query
     *
     * How to use - full query with optional parameters
     * [$filteredQuery, $filteredData] = $this->database->buildUpdateQuery($data, 'dbm_invoice', 'id=:id');
     * $this->database->queryExecute($filteredQuery, $filteredData);
     * - if not all params update: $data['amount' => 1.99, 'id' => 1]
     * $amountData = ['amount' => $data['amount']];
     * [$filteredQuery, $filteredData] = $this->database->buildUpdateQuery($amountData, 'dbm_invoice', 'id=:id');
     * $filteredData['id'] = $data['id'];
     * $this->database->queryExecute($filteredQuery, $filteredData);
     * - or basic usage
     * [$setClause, $filteredData] = $this->database->buildUpdateQuery($data);
     * $filteredQuery = "UPDATE table_name SET $setClause WHERE id=:id";
     * $this->database->queryExecute($filteredQuery, $filteredData);
     */
    public function buildUpdateQuery(array $data, ?string $table = null, ?string $condition = null): array
    {
        // Wyodrębnij klucze z warunku `WHERE`
        $conditionKeys = [];

        if ($condition) {
            preg_match_all('/\b(\w+)=:/', $condition, $matches);
            $conditionKeys = $matches[1];
        }

        // Podziel dane na `SET` (do aktualizacji) i `WHERE` (warunki)
        $whereData = array_intersect_key($data, array_flip($conditionKeys));
        $updateData = array_diff_key($data, $whereData);

        // Usuń wartości null z danych do aktualizacji
        $filteredUpdateData = array_filter($updateData, function ($value) {
            return !is_null($value);
        });

        // Budujemy klauzulę `SET`
        $setClause = implode(", ", array_map(function ($key) {
            return "$key=:$key";
        }, array_keys($filteredUpdateData)));

        // Budujemy pełne zapytanie, jeśli podano tabelę
        if ($table) {
            $filteredQuery = "UPDATE $table SET $setClause";

            // Dodajemy warunek `WHERE`, jeśli podano
            if ($condition) {
                $filteredQuery .= " WHERE $condition";
            }

            return [$filteredQuery, array_merge($filteredUpdateData, $whereData)];
        }

        // Jeśli nie podano tabeli, zwracamy tylko część `SET`
        return [$setClause, $filteredUpdateData];
    }

    /**
     * Import bazy danych
     */
    public function importSqlFile(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new ExceptionHandler("Plik SQL nie istnieje lub nie można go odczytać: " . $filePath, 500);
        }

        try {
            $sql = file_get_contents($filePath);
            return $this->multiQueryExecute($sql);
        } catch (Exception $e) {
            throw new ExceptionHandler("Błąd importu bazy danych z pliku: " . $e->getMessage(), 500, $e);
        }
    }
}
