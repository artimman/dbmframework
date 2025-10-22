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

use Lib\Search\Src\Interfaces\SearchProviderInterface;

class SearchService
{
    private array $providers = [];
    private int $defaultLimit = 20;

    public function addProvider(SearchProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Globalne wyszukiwanie we wszystkich providerach.
     *
     * @param string $query     fraza wyszukiwana
     * @param array $providers  lista providerów do użycia (opcjonalnie)
     * @param array $filters    filtry dodatkowe
     * @param int $page         numer strony (domyślnie 1)
     * @param int|null $limit   liczba wyników na stronę (domyślnie z configu)
     */
    public function searchInAllProviders(
        string $query,
        array $providers = [],
        array $filters = [],
        int $page = 1,
        ?int $limit = null
    ): SearchResultPageDto {
        $limit = $limit ?? $this->defaultLimit;
        $allResults = [];

        foreach ($this->providers as $name => $provider) {
            if (!empty($providers) && !in_array($name, $providers, true)) {
                continue;
            }

            // każdy provider zwraca tablicę SearchResultDto (bez paginacji)
            $allResults = array_merge($allResults, $provider->searchQuery($query, $filters));
        }

        $total = count($allResults);

        // globalna paginacja
        $offset = ($page - 1) * $limit;
        $items = array_slice($allResults, $offset, $limit);

        return new SearchResultPageDto($items, $total, $page, $limit);
    }
}
