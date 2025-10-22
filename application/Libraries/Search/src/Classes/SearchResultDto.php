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

class SearchResultDto
{
    public string $provider;
    public int $id;
    public ?string $title = null;
    public ?string $description = null;
    public ?string $url = null;
    public ?string $path = null;
    public ?string $createdAt = null;

    public function __construct(
        string $provider,
        int $id,
        ?string $title = null,
        ?string $description = null,
        ?string $url = null,
        ?string $path = null,
        ?string $createdAt = null
    ) {
        $this->provider = $provider;
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->path = $path;
        $this->createdAt = $createdAt;
    }
}
