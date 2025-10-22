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

/**
 * Immutable value object with DataTable params parsed from request.
 */
final class DataTableParams
{
    public readonly int $page;
    public readonly int $perPage;
    public readonly string $sort; // logical sort key
    public readonly string $dir;  // ASC|DESC
    /** @var array<string, mixed> */
    public readonly array $filters;

    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        int $page = 1,
        int $perPage = 20,
        string $sort = 'id',
        string $dir = 'DESC',
        array $filters = []
    ) {
        $this->page = max(1, $page);
        $this->perPage = max(1, min(1000, $perPage));
        $this->sort = $sort;
        $this->dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        $this->filters = $filters;
    }

    /**
     * Metoda obsługuje oba style: filter[category]=2 oraz filter_category=2.
     *
     * @param array<string,mixed> $params
     */
    public function fromRequest(array $params): self
    {
        $filters = [];

        // Styl: filter[...]
        if (isset($params['filter']) && is_array($params['filter'])) {
            foreach ($params['filter'] as $k => $v) {
                // pomijamy puste
                if ($v === '' || $v === null) {
                    continue;
                }
                $filters[(string)$k] = $v;
            }
        }

        // Styl: filter_xxx
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'filter_')) {
                if ($value === '' || $value === null) {
                    continue;
                }
                $filters[substr($key, 7)] = $value; // obetnij 'filter_'
            }
        }

        // Globalne wyszukiwanie
        // Zawsze wewnętrznie zapisujemy pod kluczem "query", żeby reszta systemu PHP i JS miała jedną konwencję.
        // W kodzie PHP mamy czytelne "query" (nie "q"), frontend i API używają krótkiego standardu "q".
        // Alias "query" działa jako fallback dla starszych linków lub alternatywnych formularzy.
        if (!empty($params['q'])) {
            $filters['query'] = (string) $params['q'];
        } elseif (!empty($params['query'])) {
            $filters['query'] = (string) $params['query'];
        }

        return new self(
            page: (int) ($params['page'] ?? 1),
            perPage: (int) ($params['per_page'] ?? 20),
            sort: (string) ($params['sort'] ?? 'id'),
            dir: (string) ($params['dir'] ?? 'DESC'),
            filters: $filters
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
