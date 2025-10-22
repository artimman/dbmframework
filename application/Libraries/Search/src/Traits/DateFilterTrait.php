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

trait DateFilterTrait
{
    /**
     * Dodaje do SQL filtry daty (`date_from`, `date_to`) jeśli istnieją.
     *
     * @param string|null $filterColumn Kolumna w bazie do filtrowania datą (np. `created_at`).
     * Jeśli `null`, filtry są pomijane.
     * @param string $sql  Aktualny fragment zapytania SQL (np. `WHERE ...`).
     * @param array  &$params Parametry bindowane do zapytania (używane w PDO).
     * @param array  $filters Tablica filtrów wyciągnięta z query stringa.
     *
     * @return string Zmienione zapytanie SQL z dopisanymi warunkami.
     */
    public function applyDateFilters(?string $filterColumn, string $sql, array &$params, array $filters): string
    {
        if ($filterColumn === null) {
            return $sql;
        }

        // date_from
        if (!empty($filters['date_from'])) {
            $sql .= " AND {$filterColumn} >= :date_from";
            $params['date_from'] = $this->normalizeDate($filters['date_from'], 'start');
        }

        // date_to
        if (!empty($filters['date_to'])) {
            $sql .= " AND {$filterColumn} <= :date_to";
            $params['date_to'] = $this->normalizeDate($filters['date_to'], 'end');
        }

        return $sql;
    }

    /**
     * Dodaje do SQL dowolne inne filtry (np. `role`, `category_id`).
     *
     * Użycie w providerze:
     * $sql = $this->applyFilters([
     *  //'field' => ['field', 'json'], // jeśli JSON
     *  //'field' => ['field', 'csv'],  // jeśli CSV
     *  'field' => 'field'              // jeśli zwykły string
     * ], $sql, $params, $filters);
     *
     * @param array  $allowedFilterColumns Mapowanie `nazwa_filtra` => `kolumna_bazy`.
     * @param string $sql  Aktualny fragment zapytania SQL.
     * @param array  &$params Parametry bindowane do zapytania.
     * @param array  $filters Tablica filtrów wyciągnięta z query stringa.
     *
     * @return string Zmienione zapytanie SQL z dopisanymi warunkami.
     */
    protected function applyFilters(array $allowedFilterColumns, string $sql, array &$params, array $filters): string
    {
        foreach ($allowedFilterColumns as $filter => $config) {
            if (!empty($filters[$filter])) {
                if (is_array($config)) {
                    [$column, $operator] = $config;
                } else {
                    $column = $config;
                    $operator = '=';
                }

                switch ($operator) {
                    case 'like':
                        $sql .= " AND {$column} LIKE :{$filter}";
                        $params[$filter] = '%' . $filters[$filter] . '%';
                        break;

                    case 'json':
                        $sql .= " AND JSON_CONTAINS({$column}, :{$filter}, '$')";
                        $params[$filter] = json_encode([$filters[$filter]]);
                        break;

                    case 'csv':
                        $sql .= " AND FIND_IN_SET(:{$filter}, {$column})";
                        $params[$filter] = $filters[$filter];
                        break;

                    default: // '='
                        $sql .= " AND {$column} = :{$filter}";
                        $params[$filter] = $filters[$filter];
                }
            }
        }

        return $sql;
    }

    /**
     * Normalizuje datę:
     * - jeśli tylko `Y-m-d`, dokleja `00:00:00` (start) lub `23:59:59` (end).
     * - jeśli zawiera godzinę (`Y-m-d H:i` albo `Y-m-d H:i:s`), zostawia bez zmian.
     */
    private function normalizeDate(string $date, string $mode): string
    {
        // Czy podano godzinę?
        if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $date)) {
            return $date; // pełna data z czasem
        }

        return $mode === 'start'
            ? $date . ' 00:00:00'
            : $date . ' 23:59:59';
    }
}
