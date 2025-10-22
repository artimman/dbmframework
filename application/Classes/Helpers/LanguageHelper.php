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

class LanguageHelper
{
    /**
     * Get raw APP_LANGUAGES string
     */
    private static function rawLanguages(): string
    {
        return getenv('APP_LANGUAGES') ?: '';
    }

    /**
     * Available languages ​​from settings
     */
    public static function getAvailableLanguages(): array
    {
        return self::rawLanguages() ? explode('|', self::rawLanguages()) : [];
    }

    /**
     * Default language = first in settings
     */
    public static function getDefaultLanguage(): ?string
    {
        $langs = self::getAvailableLanguages();
        return $langs[0] ?? null;
    }

    /**
     * Validate language code against available list
     */
    public static function isSupported(string $lang): bool
    {
        return in_array(strtoupper($lang), self::getAvailableLanguages(), true);
    }
}
