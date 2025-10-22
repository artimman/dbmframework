<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Dbm\Interfaces;

use Dbm\Classes\Dto\FileOperationDto;

interface DataFlatfileInterface
{
    public function dataFlatFile(string $type = 'content', string $space = ''): FileOperationDto;

    public function fileName(): string;
}
