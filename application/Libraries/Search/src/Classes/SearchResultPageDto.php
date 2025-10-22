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

namespace Lib\Search\Src\Classes;

use Dbm\Classes\Http\Request;

class SearchResultPageDto
{
    public array $items;
    public int $total;
    public int $page;
    public int $limit;

    public function __construct(array $items, int $total, int $page, int $limit)
    {
        $this->items = $items;
        $this->total = $total;
        $this->page = $page;
        $this->limit = $limit;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }

    public function hasResults(): bool
    {
        // total może być 0; items również powinno być sprawdzone
        return $this->total > 0 && !empty($this->items);
    }

    /**
     * Zwraca tablicę elementów do renderowania w paginacji.
     * Elementy będą liczbami stron lub stringiem '...'.
     *
     * @param int $current  bieżąca strona (1..n)
     * @param int $total    liczba stron
     * @param int $adjacents liczba sąsiednich stron po każdej stronie
     * @return array<int|string>
     */
    public function paginationButtons(int $current, int $total, int $adjacents = 2): array
    {
        $buttons = [];

        if ($total <= 1) {
            return $buttons;
        }

        $buttons[] = 1;

        $start = max(2, $current - $adjacents);
        $end   = min($total - 1, $current + $adjacents);

        if ($start > 2) {
            $buttons[] = '...';
        }

        for ($i = $start; $i <= $end; $i++) {
            $buttons[] = $i;
        }

        if ($end < $total - 1) {
            $buttons[] = '...';
        }

        if ($total > 1) {
            $buttons[] = $total;
        }

        // Usuń duplikaty liczb (ale zachowaj wszystkie '...'):
        $final = [];
        $seenNums = [];
        foreach ($buttons as $b) {
            if ($b === '...') {
                $final[] = $b;
                continue;
            }

            $num = (int) $b;
            if (in_array($num, $seenNums, true)) {
                continue; // pomiń powtórkę numeru strony
            }
            $seenNums[] = $num;
            $final[] = $num;
        }

        return $final;
    }

    /**
     * Build URL keeping current GET params and changing page.
     * $basePath - jeśli pusta -> bierze aktualny skrypt bez query
     */
    public static function buildPageUrl(int $page, string $basePath = '', array $extra = []): string
    {
        $request = new Request();
        $uri = $request->getServerParams()['REQUEST_URI'];
        $params = $request->getQueryParams();

        $base = $basePath !== '' ? $basePath : strtok($uri, '?');
        $params = array_merge($params, $extra, ['page' => $page]);

        return $base . '?' . http_build_query($params);
    }
}
