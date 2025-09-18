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

namespace Dbm\Classes;

use Dbm\Classes\Http\Request;
use Dbm\Classes\Managers\CookieManager;
use Dbm\Classes\Services\LanguageService;
use Dbm\Interfaces\TranslationInterface;
use Lib\Files\FileSystem;

class Translation implements TranslationInterface
{
    public $arrayTranslation;
    private $request;
    private $cookie;

    public function __construct()
    {
        $this->request = new Request();
        $this->cookie = new CookieManager();

        $languageService = new LanguageService($this->request, $this->cookie);
        $language = $languageService->detectLanguage();

        $this->arrayTranslation = $this->translation($language);
    }

    /**
     * Language translation
     */
    public function trans(string $key, ?array $data = null, ?array $sprint = null): string
    {
        $translation = $this->arrayTranslation;

        if (!empty($data) && array_key_exists($key, $data)) {
            (!empty($sprint)) ? $value = vsprintf($data[$key], $sprint) : $value = $data[$key];
            return $value;
        } elseif (array_key_exists($key, $translation)) {
            (!empty($sprint)) ? $value = vsprintf($translation[$key], $sprint) : $value = $translation[$key];
            return $value;
        }

        return $key;
    }

    /**
     * Load translation from all language files in directory
     */
    private function translation(?string $language = null): array
    {
        if (!$language) {
            return [];
        }

        $translations = [];
        $language = strtolower($language);
        $dir = BASE_DIRECTORY . 'translations' . DS;

        $filesystem = new FileSystem();
        $files = $filesystem->scanDirectory($dir);

        foreach ($files ?? [] as $file) {
            if (!str_ends_with($file, ".{$language}.php")) {
                continue;
            }

            $path = $dir . $file;
            if (!is_file($path)) {
                continue;
            }

            $data = require $path;
            if (is_array($data)) {
                $translations += $data;
            }
        }

        return $translations;
    }
}
