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
use Dbm\Classes\Log\Logger;
use Dbm\Interfaces\DatabaseInterface;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

class Database implements DatabaseInterface
{
    private PDO $connect;
    private ?PDOStatement $statement;
    private logger $logger;

    public function __construct(
        ?string $dbHost = null,
        ?string $dbPort = '3306',
        ?string $dbName = null,
        ?string $dbUser = null,
        ?string $dbPassword = null,
        ?string $dbCharset = 'utf8mb4',
        ?Logger $logger = null
    ) {
        // INFO: Można rozszerzyć o wstrzykiwanie Logger -> wprowadzić kontener DI
        $this->logger = $logger ?? new Logger(); // opcjonalne wstrzyknięcie loggera

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
            $msgHandler = $this->messageHandler($e, null, null, "Database connection error");
            throw new ExceptionHandler($msgHandler, 500, $e);
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
            $msgHandler = $this->messageHandler($e, $query, null, "SQL query error");
            throw new ExceptionHandler($msgHandler, 500, $e);
        }
    }

    /**
     * Obsługuje:
     *  - parametry pozycyjne (?) -> execute([...])
     *  - parametry nazwane bez i z ":" (:q, q)
     *  - wielokrotne użycie tego samego placeholdera (:q OR email LIKE :q)
     *  - parametry tablicowe (np. WHERE id IN (:ids)) => rozwija do (:ids__1, :ids__2, ...)
     *
     * TODO! Należy w starym kodzie w zapytaniach pousuwać w bindowaniu znak dwukropka ":q" na "q" itd.
     * Po poprawkach w repozytoriach (wszędzie ['q' => '...'] zamiast [':q' => '...'])
     * wystarczy usunąć tylko blok normalizacji kluczy i zamiast tego przypisać bezpośrednio: $normalized = $params;
     */
    public function queryExecute(string $query, ?array $params = [], bool $reference = false): bool
    {
        try {
            if (empty($params)) {
                $this->statement = $this->connect->prepare($query);
                return $this->statement->execute();
            }

            // Pozycyjne parametry (lista 0..n-1)
            $keys = array_keys($params);
            $isList = ($keys === range(0, count($params) - 1));
            if ($isList) {
                $this->statement = $this->connect->prepare($query);
                return $this->statement->execute($params);
            }

            // Normalizacja kluczy: usuń wiodący ":" // TODO!
            $normalized = [];
            foreach ($params as $k => $v) {
                $name = ltrim((string)$k, ':');
                $normalized[$name] = $v;
            }

            // --- Obsługa parametrów tablicowych (IN (...)) ---
            foreach ($normalized as $key => $value) {
                if (is_array($value)) {
                    if (empty($value)) {
                        // pusty array w IN => zawsze false
                        $query = str_replace(':' . $key, '(NULL)', $query);
                        unset($normalized[$key]);
                        continue;
                    }
                    $placeholders = [];
                    foreach ($value as $i => $val) {
                        $ph = ':' . $key . '__' . ($i + 1);
                        $placeholders[] = $ph;
                        $normalized[$key . '__' . ($i + 1)] = $val;
                    }
                    // zamień pojedynczy placeholder :key na listę (:key__1, :key__2, ...)
                    $query = str_replace(':' . $key, implode(',', $placeholders), $query);
                    unset($normalized[$key]);
                }
            }

            // --- Obsługa wielokrotnych wystąpień tego samego placeholdera ---
            $counters = [];
            $mapping = [];
            $newQuery = preg_replace_callback(
                '/:([A-Za-z0-9_]+)/',
                function ($m) use (&$counters, $normalized, &$mapping) {
                    $orig = $m[1];
                    if (!array_key_exists($orig, $normalized)) {
                        return $m[0]; // placeholder bez wartości → zostaw jak jest
                    }
                    $counters[$orig] = ($counters[$orig] ?? 0) + 1;
                    $i = $counters[$orig];
                    $newName = $orig . '__' . $i;
                    $mapping[$newName] = $normalized[$orig];
                    return ':' . $newName;
                },
                $query
            );

            $this->statement = $this->connect->prepare($newQuery);

            // --- Bind wszystkich parametrów ---
            $bindVars = [];
            foreach ($mapping as $newName => $value) {
                $type = $this->pdoType($value);
                $param = ':' . $newName;

                if (!$reference) {
                    $this->statement->bindValue($param, $value, $type);
                } else {
                    $bindVars[$newName] = $value;
                    $this->statement->bindParam($param, $bindVars[$newName], $type);
                }
            }

            return $this->statement->execute();
        } catch (PDOException $e) {
            $msgHandler = $this->messageHandler($e, $query, $params, "SQL query execute error");
            throw new ExceptionHandler($msgHandler, 500, $e);
        }
    }

    /**
     * Execute multiple SQL queries separated by semicolons.
     * Works with PDO and supports multiline statements like CREATE TABLE or INSERT INTO.
     *
     * INFO: Nie używać do bardzo dużych dumpów (np. pełnych eksportów PhpMyAdmin), bo parser regexowy może się pomylić przy zagnieżdżonych danych binarnych.
     * Przy importowaniu dużych dumpów warto rozważyć: mysqli::multi_query() zamiast PDO lub biblioteki jak phpmyadmin/sql-parser.
     */
    public function multiQueryExecute(string $sql): bool
    {
        try {
            // Start transaction for safety
            if (!$this->connect->inTransaction()) {
                $this->connect->beginTransaction();
            }

            // Usuń komentarze i zbędne spacje
            $sql = preg_replace('/^\s*--.*$/m', '', $sql); // usuń komentarze zaczynające się od --
            $sql = preg_replace('/^\s*#.*/m', '', $sql); // usuń komentarze zaczynające się od #
            $sql = trim($sql);

            // Rozdziel polecenia — uwzględnia CREATE, INSERT, ALTER itp.
            // Semikolon na końcu linii, ale tylko jeśli nie znajduje się wewnątrz stringa
            $queries = preg_split(
                '/;\s*(?=(?:--|INSERT|CREATE|UPDATE|DELETE|ALTER|DROP|TRUNCATE|$))/i',
                $sql
            );

            // Wykonaj każde zapytanie z osobna
            foreach ($queries as $query) {
                $query = trim($query);

                // pomiń puste linie lub komentarze
                if ($query === '' || str_starts_with($query, '--') || str_starts_with($query, '/*')) {
                    continue;
                }

                // uruchom zapytanie
                $this->connect->exec($query);
            }

            // Zatwierdź transakcję
            if ($this->connect->inTransaction()) {
                $this->connect->commit();
            }

            return true;

        } catch (PDOException $e) {
            if ($this->connect->inTransaction()) {
                $this->connect->rollBack();
            }

            $msgHandler = $this->messageHandler($e, $sql, null, "SQL multiple query execution error");
            throw new ExceptionHandler($msgHandler, 500, $e);
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

    // ---------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------
    private function pdoType($value): int
    {
        return is_int($value) ? PDO::PARAM_INT
            : (is_bool($value) ? PDO::PARAM_BOOL
            : (is_null($value) ? PDO::PARAM_NULL
            : PDO::PARAM_STR));
    }

    private function messageHandler(PDOException $exception, ?string $query = null, ?array $params = null, string $prefix = "SQL error"): string
    {
        $exceptionEnc = $this->jsonOneLine($exception->__toString());
        $queryEnc = $query ? $this->jsonOneLine($query) : 'null';
        $paramsEnc = $params ? $this->jsonOneLine($params) : 'null';

        $context = [
            'exception' => $exceptionEnc,
            'query' => $queryEnc,
            'params' => $paramsEnc,
        ];

        if ($this->logger) {
            $msgLogger = $exception->getMessage();
            $msgLogger .= PHP_EOL . "    Exception: {exception}" . PHP_EOL . "    Query: {query}" . PHP_EOL . "    Params: {params}";
            $this->logger->critical($msgLogger, $context);
        }

        $msgHandler = $prefix . ': ' . $exception->getMessage();

        if ($query) {
            $msgHandler .= '<br><span>Query: ' . htmlspecialchars($query, ENT_QUOTES) . '</span>';
        }

        if ($params) {
            $msgHandler .= '<br><span>Params: ' . htmlspecialchars($paramsEnc, ENT_QUOTES) . '</span>';
        }

        return $msgHandler;
    }

    /** Return a single-line JSON for logging. */
    private function jsonOneLine($value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '"<json_encode_error>"' : $json;
    }
}
