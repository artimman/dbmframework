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

class FileSystem
{
    public function fileMultiDelete(mixed $files): ?string
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);

                    if (is_file($file)) {
                        return "Something went wrong! The file $file has not been deleted.";
                    }
                } else {
                    return "File $file does not exist!";
                }
            }
        } elseif (file_exists($files)) {
            unlink($files);

            if (is_file($files)) {
                return "Something went wrong! The file $files has not been deleted.";
            }
        } else {
            return "File $files does not exist!";
        }

        return null;
    }

    public function deleteFile(string $filePath): void
    {
        if (is_file($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
    }

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

    public function editFile(string $filePath, string $fileContent): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File does not exist: $filePath");
        }

        if (file_put_contents($filePath, $fileContent) === false) {
            throw new RuntimeException("Unable to edit file: $filePath");
        }
    }

    public function renameFile(string $sourcePath, string $destinationPath): void
    {
        if (!file_exists($sourcePath)) {
            throw new RuntimeException("File does not exist: $sourcePath");
        }

        if (!rename($sourcePath, $destinationPath)) {
            throw new RuntimeException("Failed to rename $sourcePath to $destinationPath");
        }
    }

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

    public function scanDirectory(string $directory, int $sort = 0, array $arraySkip = ['..', '.']): ?array
    {
        if (is_dir($directory)) {
            return array_diff(scandir($directory, $sort), $arraySkip);
        }

        return null;
    }

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

    public function contentPreview(string $pathFile): ?string
    {
        if (is_file($pathFile) && file_exists($pathFile) && (filesize($pathFile) > 0)) {
            $contentPreview = file_get_contents($pathFile);
            $contentPreview = str_replace([PHP_EOL], ["<br>"], $contentPreview);
        }

        return $contentPreview ?? null;
    }
}
