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

interface TranslationInterface
{
    /**
     * Translates the given language key.
     *
     * @param string $key The translation key, e.g., "install.button.link.continue".
     * @param array|null $data (optional) An array of replaced translations; if provided, takes precedence over file translations.
     * @param array|null $sprint (optional) Arguments to be substituted by vsprintf() in the translation.
     *
     * @return string The translated text, or the key if no translation exists.
     */
    public function trans(string $key, ?array $data = null, ?array $sprint = null): string;
}
