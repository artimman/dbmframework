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

namespace Dbm\Views;

use RuntimeException;

/**
 * TemplateCache
 *
 * Odpowiada za zarządzanie plikami cache dla skompilowanych szablonów.
 * Dba o tworzenie katalogu cache, generowanie nazw plików i kontrolę świeżości.
 */
class TemplateCache
{
    /**
     * @var string Katalog, w którym przechowywane są pliki cache.
     */
    private string $cacheDir;

    /**
     * Konstruktor. Tworzy katalog cache, jeśli nie istnieje.
     *
     * @param string $cacheDir Ścieżka bazowa do katalogu cache
     * @throws RuntimeException jeśli nie można utworzyć katalogu
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, DS) . DS;

        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
                throw new RuntimeException("Unable to create cache directory: {$this->cacheDir}");
            }
        }

        if (!is_writable($this->cacheDir)) {
            throw new RuntimeException("Cache directory is not writable: {$this->cacheDir}");
        }
    }

    /**
     * Zwraca pełną ścieżkę do pliku cache dla danego szablonu.
     *
     * @param string $template Nazwa szablonu, np. "layouts/main.phtml"
     */
    public function getCachePath(string $template): string
    {
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', ltrim($template, '/\\'));
        return $this->cacheDir . $key . '.php';
    }

    /**
     * Sprawdza, czy plik cache jest aktualny względem oryginału.
     *
     * @param string $templatePath Pełna ścieżka do oryginalnego pliku szablonu
     * @param string $cachePath    Ścieżka do pliku cache
     */
    public function isFresh(string $templatePath, string $cachePath): bool
    {
        if (!is_file($cachePath) || !is_file($templatePath)) {
            return false;
        }

        return filemtime($cachePath) >= filemtime($templatePath);
    }

    /**
     * Zapisuje skompilowany kod PHP do pliku cache.
     * Zapis odbywa się atomowo (do pliku tymczasowego, potem rename()).
     *
     * @param string $cachePath Ścieżka do pliku cache
     * @param string $code       Zawartość do zapisania
     * @throws RuntimeException jeśli zapis się nie powiedzie
     */
    public function write(string $cachePath, string $code): void
    {
        $tmpPath = $cachePath . '.' . uniqid('tmp_', true);

        if (file_put_contents($tmpPath, $code) === false) {
            throw new RuntimeException("Unable to write temporary cache file: {$tmpPath}");
        }

        // Bezpieczna zamiana (atomiczna w systemach UNIX)
        if (!@rename($tmpPath, $cachePath)) {
            @unlink($tmpPath);
            throw new RuntimeException("Failed to move cache file into place: {$cachePath}");
        }

        @chmod($cachePath, 0644);
    }

    /**
     * Usuwa wszystkie pliki z katalogu cache.
     *
     * @return int Liczba usuniętych plików
     */
    public function clear(): int
    {
        $count = 0;
        foreach (glob($this->cacheDir . '*.php') as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Usuwa tylko nieaktualne pliki cache (jeśli ich źródła już nie istnieją).
     *
     * @param string $templatesDir Katalog źródłowych szablonów
     * @return int Liczba usuniętych plików
     */
    public function clearExpired(string $templatesDir): int
    {
        $count = 0;
        foreach (glob($this->cacheDir . '*.php') as $cacheFile) {
            $key = basename($cacheFile, '.php');
            $tplName = str_replace('_', DS, $key) . '.phtml';
            $tplPath = rtrim($templatesDir, DS) . DS . $tplName;

            if (!file_exists($tplPath)) {
                if (@unlink($cacheFile)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Zwraca katalog cache.
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }
}
