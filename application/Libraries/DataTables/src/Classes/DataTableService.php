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
 */

declare(strict_types=1);

namespace Lib\DataTables\Src\Classes;

use Lib\DataTables\Src\Interfaces\DatabaseInterface;
use Lib\DataTables\Src\Interfaces\RepositoryInterface;

final class DataTableService
{
    private DataTableParams $params;
    private ?DatabaseInterface $database;
    private ?TestBuiltQuery $lastQuery = null;

    public function __construct(?DatabaseInterface $database = null, ?DataTableParams $params = null)
    {
        $this->database = $database;
        $this->params = $params ?? new DataTableParams();
    }

    public function withDatabase(DatabaseInterface $database): self
    {
        $clone = clone $this;
        $clone->database = $database;
        return $clone;
    }

    public function withParams(DataTableParams $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    public function params(): DataTableParams
    {
        return $this->params;
    }

    /** Standardowa paginacja z repo */
    public function paginate(RepositoryInterface $repo): DataTableResult
    {
        $records = $repo->list(
            $this->params->perPage,
            $this->params->offset(),
            $this->params->filters,
            $this->params->sort,
            $this->params->dir,
        );

        if (method_exists($repo, 'getLastBuiltQuery')) {
            $this->lastQuery = $repo->getLastBuiltQuery();
        }

        $total = $repo->count($this->params->filters);

        return new DataTableResult($records, $total, $this->params);
    }

    /**
     * Paginacja na surowym SELECT-cie.
     * $baseSql – dowolny SELECT bez LIMIT/OFFSET (ORDER BY może być, ale nie trzeba).
     * $maps = [
     *   'sortable'   => ['sortKey'=>'aliasKolumny', ...],      // używamy aliasów wybranych w $baseSql
     *   'filterable' => ['filter_key'=>'aliasKolumny', ...],   // aliasy muszą być w SELECT-cie
     *   'searchable' => ['alias1','alias2', ...],              // aliasy do globalnego szukania
     * ]
     */
    public function paginateRaw(string $baseSql, array $maps = []): DataTableResult
    {
        if (!$this->database) {
            throw new \RuntimeException('DataTableService: database not set. Pass DatabaseInterface in constructor or call withDatabase().');
        }

        $sortable = $maps['sortable'] ?? [];
        $filterable = $maps['filterable'] ?? [];
        $searchable = $maps['searchable'] ?? [];

        // WHERE (filtry + search)
        [$whereSql, $binds] = $this->buildWhereRaw(
            $this->params->filters,
            $filterable,
            $searchable
        );

        // ORDER BY
        $sortKey = $this->params->sort;
        $sortAlias = $sortable[$sortKey] ?? null;
        $orderSql = $sortAlias ? (' ORDER BY datatable.' . $sortAlias . ' ' . $this->params->dir) : '';

        // SELECT (data)
        $listSql = 'SELECT * FROM (' . $baseSql . ') datatable' . $whereSql . $orderSql . ' LIMIT :_limit OFFSET :_offset';
        $listBinds = $binds;
        $listBinds[':_limit'] = $this->params->perPage;
        $listBinds[':_offset'] = $this->params->offset();

        // Get last query
        $this->lastQuery = new TestBuiltQuery($listSql, $listBinds);

        $records = $this->database->fetchAll($listSql, $listBinds);

        // COUNT (bez limit/offset)
        $countSql = 'SELECT COUNT(*) AS _cnt FROM (' . $baseSql . ') datatable' . $whereSql;
        $row = $this->database->fetch($countSql, $binds);
        $total = (int)($row['_cnt'] ?? 0);

        return new DataTableResult($records, $total, $this->params);
    }

    /**
     * Do celów testowych – pobierz ostatnio utworzone zapytanie
     */
    public function getLastBuiltQuery(): ?TestBuiltQuery
    {
        return $this->lastQuery;
    }

    /**
     * Buduje WHERE dla paginateRaw.
     * Oczekuje, że aliasy w $filterable/$searchable istnieją w SELECT (jako aliasy kolumn).
     *
     * @param array<string,mixed> $filters
     * @param array<string,string> $filterable // filter_key => alias
     * @param string[] $searchable // aliasy
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhereRaw(array $filters, array $filterable, array $searchable): array
    {
        if (!$filters) {
            return ['', []];
        }

        $clauses = [];
        $params = [];

        // Globalne szukanie
        if (!empty($filters['query']) && is_string($filters['query'])) {
            $like = trim($filters['query']);

            if ($like !== '' && $searchable) {
                $parts = [];

                foreach (array_values($searchable) as $i => $alias) {
                    $ph = ':_q_' . $i;
                    $parts[] = 'datatable.' . $alias . ' LIKE ' . $ph;
                    $params[$ph] = '%' . $like . '%';
                }

                if ($parts) {
                    $clauses[] = '(' . implode(' OR ', $parts) . ')';
                }
            }
        }

        // Zwykłe filtry
        foreach ($filters as $key => $value) {
            if ($key === 'query') {
                continue;
            }

            if ($value === '' || $value === null) {
                continue;
            }

            if (!isset($filterable[$key])) {
                continue;
            }

            $alias = $filterable[$key];
            $ph    = ':_f_' . $key;
            $clauses[]   = 'datatable.' . $alias . ' = ' . $ph;
            $params[$ph] = $value;
        }

        if (!$clauses) {
            return ['', []];
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }
}
