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

use Lib\DataTables\Src\Interfaces\DatabaseInterface;
use Lib\DataTables\Src\Interfaces\RepositoryInterface;

class JoinedRepository implements RepositoryInterface
{
    private ?TestBuiltQuery $lastQuery = null;

    public function __construct(
        protected DatabaseInterface $database,
        protected string $table,
        protected string $primaryKey = 'id',
        /** @var string[] */
        protected array $joins = [],
        /** @var array<string,string> */
        protected array $selectMap = ['id' => 'id'],
        /** @var array<string,string> */
        protected array $sortableMap = ['id' => 'id'],
        /** @var array<string,string> */
        protected array $filterableMap = [],
        /** @var string[] logical keys to use in global search */
        protected array $searchable = []
    ) {
    }

    /** @inheritDoc */
    public function list(int $limit, int $offset, array $filters, string $sortColumn, string $sortOrder): array
    {
        [$whereSql, $params] = $this->buildWhere($filters);

        $orderExpr = $this->sortableMap[$sortColumn] ?? $this->primaryKey;
        $dir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $select = implode(', ', array_values($this->selectMap));

        $sql = 'SELECT ' . $select
            . ' FROM ' . $this->table
            . (!empty($this->joins) ? ' ' . implode(' ', $this->joins) : '')
            . (!empty($whereSql) ? ' ' . $whereSql : '')
            . ' ORDER BY ' . $orderExpr . ' ' . $dir
            . ' LIMIT :_limit OFFSET :_offset';

        $params[':_limit'] = $limit;
        $params[':_offset'] = $offset;

        $this->lastQuery = new TestBuiltQuery($sql, $params);

        return $this->database->fetchAll($sql, $params);
    }

    /** @inheritDoc */
    public function count(array $filters): int
    {
        [$whereSql, $params] = $this->buildWhere($filters);

        $sql = 'SELECT COUNT(*) AS _cnt FROM ' . $this->table
             . (!empty($this->joins) ? ' ' . implode(' ', $this->joins) : '')
             . (!empty($whereSql) ? ' ' . $whereSql : '');

        $row = $this->database->fetch($sql, $params);

        return (int)($row['_cnt'] ?? 0);
    }

    /**
     * Do celów testowych – pobierz ostatnio utworzone zapytanie
     */
    public function getLastBuiltQuery(): ?TestBuiltQuery
    {
        return $this->lastQuery;
    }

    /**
     * Można nadpisać w konfiguracji tabeli, aby narzucić stałe warunki (np. status='active').
     *
     * @return array{0:string,1:array} [SQL fragment, parametry]
     *
     * Przykład użycia w "NameConfigDataTable":
     * protected function getBaseWhere(): array {
     *  return ['a.status = :_status', [':_status' => 'active']];
     * }
     */
    protected function getBaseWhere(): array
    {
        return ['', []]; // domyślnie brak dodatkowego WHERE
    }

    /**
     * Buduje klauzulę WHERE na podstawie filtrów i bazowych warunków
     */
    protected function buildWhere(array $filters): array
    {
        [$baseClause, $baseParams] = $this->getBaseWhere();
        [$filterClause, $filterParams] = $this->buildFilters($filters);
        [$searchClause, $searchParams] = $this->buildSearchClause($filters);

        $clauses = array_filter([$baseClause, $filterClause, $searchClause]);
        $params  = array_merge($baseParams, $filterParams, $searchParams);

        if (!$clauses) {
            return ['', []];
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * Logika filtrów użytkownika (wydzielona z buildWhere)
     */
    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        foreach ($filters as $key => $value) {
            if (($value === '' || $value === null) || !isset($this->filterableMap[$key])) {
                continue;
            }

            $expr = $this->filterableMap[$key];
            $ph   = ':_f_' . $key;

            if (is_string($value) && ctype_digit($value)) {
                $value = (int) $value;
            }

            $clauses[]   = $expr . ' = ' . $ph;
            $params[$ph] = $value;
        }

        if (!$clauses) {
            return ['', []];
        }

        return ['(' . implode(' AND ', $clauses) . ')', $params];
    }

    /**
     * Globalne wyszukiwanie po wielu kolumnach (query)
     */
    private function buildSearchClause(array $filters): array
    {
        if (empty($filters['query']) || !is_string($filters['query']) || !$this->searchable) {
            return ['', []];
        }

        $clauses = [];
        $params  = [];
        $term    = trim($filters['query']);

        if ($term === '') {
            return ['', []];
        }

        foreach ($this->searchable as $i => $logicalKey) {
            if (!isset($this->filterableMap[$logicalKey]) && !isset($this->selectMap[$logicalKey])) {
                continue;
            }

            $expr = $this->filterableMap[$logicalKey]
                ?? preg_replace('/\s+AS\s+.+$/i', '', $this->selectMap[$logicalKey]);

            $ph = ':_q_' . $i;
            $clauses[] = $expr . ' LIKE ' . $ph;
            $params[$ph] = '%' . $term . '%';
        }

        if (!$clauses) {
            return ['', []];
        }

        return ['(' . implode(' OR ', $clauses) . ')', $params];
    }
}
