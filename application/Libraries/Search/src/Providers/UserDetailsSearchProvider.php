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

namespace Lib\Search\Src\Providers;

use Dbm\Interfaces\DatabaseInterface;
use Lib\Search\Src\Factories\SearchResultFactory;
use Lib\Search\Src\Interfaces\SearchProviderInterface;
use Lib\Search\Src\Traits\DateFilterTrait;
use Lib\Search\Src\Traits\SearchResultMapperTrait;

class UserDetailsSearchProvider implements SearchProviderInterface
{
    use DateFilterTrait;
    use SearchResultMapperTrait;

    private ?DatabaseInterface $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->database = $database;
    }

    /**
     * Etykieta providera
     */
    public function getName(): string
    {
        return 'users_details';
    }

    public function searchQuery(string $query, array $filters = []): array
    {
        $sql = "SELECT id, fullname, profession, biography, business, address 
            FROM dbm_user_details 
            WHERE (fullname LIKE :q OR profession LIKE :q OR biography LIKE :q 
            OR business LIKE :q OR address LIKE :q)";

        $params = ['q' => "%$query%"];

        // Tabela nie ma 'created', więc i filtra date_form - date_to = null
        $sql = $this->applyDateFilters(null, $sql, $params, $filters);

        $this->database->queryExecute($sql, $params);
        $rows = $this->database->fetchAllObject() ?: [];

        return $this->mapRows($rows, fn ($row) => SearchResultFactory::fromUserDetails($row, $this->getName()));
    }
}
