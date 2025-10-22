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

namespace Dbm\Classes\Helpers;

use Dbm\Classes\Translation;

class TranslationLoader
{
    private string $translationsPath;

    public function __construct(string $translationsPath = __DIR__ . '/../../../translations')
    {
        $this->translationsPath = $translationsPath;
    }

    public function load(): Translation
    {
        if ($this->shouldLoad()) {
            return new Translation();
        }

        return new class () extends Translation {
            public function trans(string $key, ?array $data = null, ?array $sprint = null): string
            {
                return $key;
            }
        };
    }

    private function shouldLoad(): bool
    {
        $languages = getenv('APP_LANGUAGES');

        if (empty($languages)) {
            return false;
        }

        if (!is_dir($this->translationsPath)) {
            return false;
        }

        return !empty(glob($this->translationsPath . '/*.php'));
    }
}
