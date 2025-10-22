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

namespace Lib\DataTables\Src\Interfaces;

/**
 * Defines a contract for DataTable configuration classes.
 *
 * Each ConfigDataTable class (e.g. BlogConfigDataTable, UserConfigDataTable)
 * serves as a bridge between the DataTables library and the application domain.
 *
 * It provides metadata about the table (name, primary key, joins, mappings)
 * and may also expose filters, actions, and other context-specific options.
 */
interface ConfigDataTableInterface
{
    /**
     * Get full DataTable schema configuration.
     *
     * Should include table name, primary key, joins, selectMap,
     * sortableMap, filterableMap, and searchable fields.
     *
     * @return array<string,mixed>
     */
    public function getTableConfig(): array;

    /**
     * Get available filters for this DataTable.
     *
     * Filters are typically returned as an associative array:
     * [
     *   'filter_key' => [
     *      'label' => 'Filter Label',
     *      'options' => [
     *          ['value' => '1', 'label' => 'Option A'],
     *          ['value' => '2', 'label' => 'Option B'],
     *      ]
     *   ],
     * ]
     *
     * @return array<string,mixed>
     */
    public function getFilters(): array;

    /**
     * Get action buttons available for this DataTable.
     *
     * Example:
     * [
     *   ['label' => 'Add New', 'url' => '/articles/new', 'class' => 'btn-primary'],
     *   ['label' => 'Export CSV', 'url' => '/articles/export', 'class' => 'btn-secondary'],
     * ]
     *
     * @return array<int,array<string,mixed>>
     */
    public function getButtons(): array;

    /**
     * Inject custom rows into the DataTable output.
     *
     * This method allows to add rows such as:
     *  - notice rows (informational message across the table)
     *  - custom HTML rows
     *  - summary rows (totals, aggregates)
     *
     * @param array<int,array<string,mixed>> $rows    Current data rows.
     * @param array<int,array<string,mixed>> $columns Table schema definition.
     *
     * @return array<int,array<string,mixed>> Modified rows with custom rows included.
     */
    public static function getCustomRows(array $rows, array $columns): array;

    /**
     * Get DataTable rendering mode.
     * Allowed values: "PHP", "AJAX", "API".
     *
     * @param string|null $mode Optional override.
     * @return string
     */
    public static function getMode(?string $mode = null): string;

    /**
     * Get base URL used for AJAX/API requests.
     *
     * @param string|null $url Optional override.
     * @return string
     */
    public static function getUrl(?string $url = null): ?string;
}
