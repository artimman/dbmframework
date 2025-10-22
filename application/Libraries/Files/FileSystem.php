<?php
/**
 * Library: Filesystem
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @package Lib\FileSystem
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Lib\Files;

use RuntimeException;

/**
 * Class FileSystem
 */
class FileSystem
{
    /**
     * Odczytuje zawartość pliku.
     *
     * @param string $filePath Ścieżka do pliku.
     * @return string|null Zawartość pliku lub null, jeśli nie istnieje lub jest pusty.
     */
    public function readFile(string $filePath): ?string
    {
        if (!is_file($filePath) || !file_exists($filePath) || filesize($filePath) === 0) {
            return null;
        }

        return file_get_contents($filePath);
    }

    /**
     * Zapisuje nowy plik lub nadpisuje istniejący.
     *
     * @param string $filePath Ścieżka do pliku.
     * @param string $fileContent Treść do zapisania.
     * @param int $chmod Uprawnienia dla pliku i katalogu.
     * @throws RuntimeException Jeśli nie można utworzyć katalogu, zapisać lub ustawić uprawnień.
     */
    public function saveFile(string $filePath, string $fileContent, int $chmod = 0755): void
    {
        $directory = dirname($filePath);

        if (!is_dir($directory) && !mkdir($directory, $chmod, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create directory: $directory");
        }

        if (file_put_contents($filePath, $fileContent) === false) {
            throw new RuntimeException("Unable to write to file: $filePath");
        }

        if (!chmod($filePath, $chmod)) {
            throw new RuntimeException("Failed to set permissions for file: $filePath");
        }
    }

    /**
     * Edytuje istniejący plik.
     *
     * @param string $filePath Ścieżka do pliku.
     * @param string $fileContent Nowa zawartość pliku.
     * @throws RuntimeException Jeśli plik nie istnieje lub nie można zapisać.
     */
    public function editFile(string $filePath, string $fileContent): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File does not exist: $filePath");
        }

        if (file_put_contents($filePath, $fileContent) === false) {
            throw new RuntimeException("Unable to edit file: $filePath");
        }
    }

    /**
     * Usuwa pojedynczy plik.
     *
     * @param string $filePath Ścieżka do pliku.
     */
    public function deleteFile(string $filePath): void
    {
        if (is_file($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * [?] Kopiuje plik.
     *
     * @param string $sourcePath
     * @param string|null $backupPath
     */
    public function copyFile(string $sourcePath, ?string $backupPath = null): void
    {
        if (!file_exists($sourcePath)) {
            throw new RuntimeException("Source file does not exist: $sourcePath");
        }

        if ($backupPath === null) {
            $backupPath = $sourcePath . '.bak';
        }

        if (!copy($sourcePath, $backupPath)) {
            throw new RuntimeException("Failed to copy $sourcePath to $backupPath");
        }
    }

    /**
     * [?] Zmienia nazwę pliku lub katalogu.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     */
    public function renameFile(string $sourcePath, string $destinationPath): void
    {
        if (!file_exists($sourcePath)) {
            throw new RuntimeException("File does not exist: $sourcePath");
        }

        if (!rename($sourcePath, $destinationPath)) {
            throw new RuntimeException("Failed to rename $sourcePath to $destinationPath");
        }
    }

    /**
     * Usuwa wiele plików (lub jeden) i zwraca komunikat o błędzie, jeśli coś pójdzie nie tak.
     *
     * @param string|array $images Ścieżka lub tablica ścieżek do plików.
     * @return string|null Komunikat błędu lub null, jeśli wszystko OK.
     */
    public function fileMultiDelete($images): ?string
    {
        if (is_array($images)) {
            foreach ($images as $image) {
                if (file_exists($image)) {
                    unlink($image);

                    if (is_file($image)) {
                        return "Something went wrong! The file $image has not been deleted.";
                    }
                } else {
                    return "File $image does not exist!";
                }
            }
        } elseif (file_exists($images)) {
            unlink($images);

            if (is_file($images)) {
                return "Something went wrong! The file $images has not been deleted.";
            }
        } else {
            return "File $images does not exist!";
        }

        return null;
    }

    /**
     * Odczytuje zawartość pliku za pomocą strumienia (fopen/fread).
     * Użyteczne, gdy chcesz kontrolować tryb odczytu (np. binarny).
     *
     * @param string $filePath Ścieżka do pliku.
     * @param string $mode Tryb odczytu (np. 'r', 'rb', 'r+') TODO! append -> mode 'a', etc.
     * @return string|null Zawartość pliku lub null, jeśli nie istnieje lub jest pusty.
     */
    public function readFileStream(string $filePath, string $mode = 'r'): ?string
    {
        if (!is_file($filePath) || filesize($filePath) === 0) {
            return null;
        }

        $handle = fopen($filePath, $mode);

        if ($handle === false) {
            throw new RuntimeException("Unable to open file for reading: $filePath");
        }

        // Blokada współdzielona (tylko odczyt)
        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            throw new RuntimeException("Unable to lock file for reading: $filePath");
        }

        $content = fread($handle, filesize($filePath));
        flock($handle, LOCK_UN);
        fclose($handle);

        return $content !== false ? $content : null;
    }

    /**
     * Zapisuje zawartość do pliku z blokadą zapisu.
     * Gwarantuje, że tylko jeden proces zapisuje w danym momencie.
     *
     * @param string $filePath Ścieżka do pliku.
     * @param string $content Zawartość
     * @param string $mode Tryb odczytu (np. 'w', 'wb').
     * @param int $chmod Uprawnienia
     */
    public function writeFileStream(string $filePath, string $content, string $mode = 'w', int $chmod = 0644): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create directory: $directory");
        }

        $handle = fopen($filePath, $mode);
        if ($handle === false) {
            throw new RuntimeException("Unable to open file for writing: $filePath");
        }

        // Blokada wyłączna (tylko jeden zapis)
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException("Unable to lock file for writing: $filePath");
        }

        $bytes = fwrite($handle, $content);
        if ($bytes === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new RuntimeException("Unable to write to file: $filePath");
        }

        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        chmod($filePath, $chmod);
    }

    /**
     * Zwraca listę plików w katalogu, z pominięciem określonych elementów.
     *
     * @param string $directory Ścieżka do katalogu.
     * @param int $sort Sortowanie (0 = rosnąco, 1 = malejąco).
     * @param array $arraySkip Pliki/katalogi do pominięcia.
     * @return array|null Lista plików lub null, jeśli katalog nie istnieje.
     */
    public function scanDirectory(string $directory, int $sort = 0, array $arraySkip = ['..', '.']): ?array
    {
        if (is_dir($directory)) {
            return array_diff(scandir($directory, $sort), $arraySkip);
        }

        return null;
    }

    /**
     * [?] Usuwa katalog i pliki katalogu.
     *
     * @param string $directory
     */
    public function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                $this->deleteFile($path);
            }
        }

        rmdir($directory);
    }

    /**
     * Zwraca zawartość pliku w formacie HTML (zamienia nowe linie na <br>).
     *
     * @param string $pathFile Ścieżka do pliku.
     * @return string|null Zawartość pliku jako HTML lub null.
     */
    public function contentPreview(string $pathFile): ?string
    {
        if (is_file($pathFile) && file_exists($pathFile) && (filesize($pathFile) > 0)) {
            $contentPreview = file_get_contents($pathFile);
            $contentPreview = str_replace([PHP_EOL], ["<br>"], $contentPreview);
        }

        return $contentPreview ?? null;
    }
}
