<?php
/**
 * Library: DbM Search Engine
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * INFO! W klasie jest SQL, czy przenieść klasę z namespace Lib/ do App/ ?
 * Generalnie bibliotekę można pozostawić bez zmian i skonfigurować w warstwie projektu 'App'.
 */

declare(strict_types=1);

namespace Lib\Search\Src\Providers;

use Dbm\Interfaces\DatabaseInterface;
use Lib\Search\Src\Factories\SearchResultFactory;
use Lib\Search\Src\Interfaces\SearchProviderInterface;
use Lib\Search\Src\Traits\DateFilterTrait;
use Lib\Search\Src\Traits\SearchResultMapperTrait;

class UserSearchProvider implements SearchProviderInterface
{
    use DateFilterTrait;
    use SearchResultMapperTrait;

    private ?DatabaseInterface $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->database = $database;
    }

    /**
     * Unikalna etykieta providera
     */
    public function getName(): string
    {
        return 'users';
    }

    public function searchQuery(string $query, array $filters = []): array
    {
        $sql = "SELECT id, login, email, roles, created_at 
                FROM dbm_user
                WHERE (login LIKE :q OR email LIKE :q)";
        $params = ['q' => "%$query%"];

        $sql = $this->applyDateFilters('created', $sql, $params, $filters);
        $sql = $this->applyFilters(['roles' => 'roles'], $sql, $params, $filters);

        $this->database->queryExecute($sql, $params);
        $rows = $this->database->fetchAllObject() ?: [];

        return $this->mapRows($rows, fn ($row) => SearchResultFactory::fromUser($row, $this->getName()));
    }
}
