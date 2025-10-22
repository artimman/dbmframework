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

namespace Lib\Search\Src\Factories;

use Dbm\Classes\Http\Request;
use Lib\Search\Src\Classes\SearchHelper;
use Lib\Search\Src\Classes\SearchResultDto;

class SearchResultFactory
{
    private static ?Request $request = null;

    public function __construct()
    {
        self::$request ??= new Request();
    }

    public static function fromUser(object $row, string $provider): SearchResultDto
    {
        return new SearchResultDto(
            provider: $provider,
            id: (int) $row->id,
            title: $row->login,
            description: $row->email,
            url: '#user-' . $row->id,
            path: 'panel_search',
            createdAt: $row->created ?? null
        );
    }

    public static function fromUserDetails(object $row, string $provider): SearchResultDto
    {
        return new SearchResultDto(
            provider: $provider,
            id: (int) $row->id,
            title: $row->fullname,
            description: $row->profession,
            url: '#user-details-' . $row->id,
            path: 'panel_search',
            createdAt: null
        );
    }

    public static function fromArticle(object $row, string $provider): SearchResultDto
    {
        self::$request ??= new Request();

        return new SearchResultDto(
            provider: $provider,
            id: (int) $row->id,
            title: SearchHelper::highlightAndTruncate($row->page_header, self::$request->getQuery('q')),
            description: SearchHelper::highlightAndTruncate($row->page_content, self::$request->getQuery('q')), // clearContent($row->page_content)
            url: '#article-' . $row->id,
            path: 'panel_manage_articles',
            createdAt: $row->created ?? null
        );
    }
}
