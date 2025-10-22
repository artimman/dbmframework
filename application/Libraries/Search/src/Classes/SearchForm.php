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

use DateTime;

class SearchForm
{
    private array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Query
     */
    public function sanitizeQuery(string $query): string
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            $this->addError('q', 'Wyszukiwana fraza musi mieć co najmniej 3 znaki.');
            return '';
        }

        if (mb_strlen($query) > 200) {
            $this->addError('q', 'Wyszukiwana fraza jest za długa (max 200 znaków).');
        }

        return mb_substr($query, 0, 200);
    }

    /**
     * Dostępne filtry
     */
    public function extractFilters(array $queries): array
    {
        $filters = [];

        foreach ($queries as $key => $value) {
            // Wykluczamy "q" i inne parametry, które nie są filtrami
            if (in_array($key, ['q'], true)) {
                continue;
            }

            // Normalizacja pustych wartości
            if ($value === '' || $value === null) {
                continue;
            }

            $filters[$key] = $value;
        }

        // wymuszamy istnienie klucza providers
        if (!isset($filters['providers'])) {
            $filters['providers'] = [];
        }

        // UWAGA! Walidacja - dodawana ręcznie, osobno dla każdgo filtra
        if (isset($filters['date_from']) && !DateTime::createFromFormat('Y-m-d', $filters['date_from'])) {
            $filters['date_from'] = null;
        }

        if (isset($filters['date_to']) && !DateTime::createFromFormat('Y-m-d', $filters['date_to'])) {
            $filters['date_to'] = null;
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to']) &&
            $filters['date_from'] > $filters['date_to']) {
            $filters['date_to'] = null; // lub zamienić kolejność
        }

        if (isset($filters['status']) && !in_array($filters['status'], ['active', 'inactive', 'new'], true)) {
            $filters['status'] = null;
        }

        return $filters;
    }
}
