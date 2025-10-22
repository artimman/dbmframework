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

class ArticleSearchProvider implements SearchProviderInterface
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
        return 'articles';
    }

    public function searchQuery(string $query, array $filters = []): array
    {
        $sql = "SELECT id, meta_title, page_header, page_content, status, created 
            FROM dbm_article 
            WHERE (meta_title LIKE :q OR page_header LIKE :q OR page_content LIKE :q)";

        $params = ['q' => "%$query%"];

        $sql = $this->applyDateFilters('created', $sql, $params, $filters);
        $sql = $this->applyFilters(['status' => 'status'], $sql, $params, $filters);

        $this->database->queryExecute($sql, $params);
        $rows = $this->database->fetchAllObject() ?: [];

        return $this->mapRows($rows, fn ($row) => SearchResultFactory::fromArticle($row, $this->getName()));
    }

    // INFO: Jest w SearchResultMapperTrait ?
    /* private function mapRows(array $rows, callable $mapper): array
    {
        return array_map($mapper, $rows);
    }*/
}
