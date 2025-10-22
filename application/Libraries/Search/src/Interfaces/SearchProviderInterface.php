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

namespace Lib\Search\Src\Interfaces;

interface SearchProviderInterface
{
    /**
     * Unique provider name (used as key in aggregated results).
     */
    public function getName(): string;

    /**
     * Execute provider-specific search.
     * @param string $query
     * @param array<string, mixed> $filters
     * @return array
     */
    public function searchQuery(string $query, array $filters = []): array;
}
