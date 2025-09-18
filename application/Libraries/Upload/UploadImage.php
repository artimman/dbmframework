<?php
/**
 * Library: Image Upload
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @package Lib\ResizeUploadImage
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * Example usage:
 * ```php
 * $uploadImage = new UploadImage();
 *
 * $uploadImage->setTargetDir('images/'); // optional
 * $uploadImage->setAllowedTypes(['jpg', 'png', 'webp']); // optional
 * $uploadImage->setMaxFileSize(); // optional
 * $uploadImage->setMaxWidth(); // optional
 * $uploadImage->setMaxHeight(); // optional
 * $uploadImage->setRenameIfExist(); // optional
 * $uploadImage->setTranslator([
 *  'pl' => [
 *   'Invalid file upload.' => 'Nieprawidłowy plik.',
 *   // etc.
 *  ],
 * ], 'pl'); // optional
 *
 * $result = $uploadImage->uploadImage($uploadedFile);
 *
 * if ($result['status'] === 'success') {
 *     echo "Uploaded file: " . $result['data'];
 * } else {
 *     echo "Error: " . $result['message'];
 * }
 * ```
 */

declare(strict_types=1);

namespace Lib\Upload;

use Exception;

class UploadImage
{
    private string $targetDir = "upload/";
    private array $allowedTypes = ["jpg", "jpeg", "png", "gif", "webp"];
    private int $maxFileSize = 6291456; // 6MB (1MB = 1048576 in bytes)
    private ?int $maxWidth = null;
    private ?int $maxHeight = null;
    private bool $renameIfExist = false;
    private array $translations = [];
    private string $lang = 'en';

    public function uploadImage(array $file): array
    {
        try {
            if (!isset($file['tmp_name'], $file['name'], $file['size'])) {
                throw new Exception($this->trans('Invalid file upload.'));
            }

            $this->validateDirectory($this->targetDir);
            $this->validateFileSize($file['size']);

            $fileTempName = $file['tmp_name'];
            $fileName = $this->sanitizeFileName(basename($file['name']));
            $fileName = strtolower($fileName);

            $imageExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $this->validateAllowedTypes($imageExt);

            $this->validateImageDimensions($fileTempName);

            if ($this->renameIfExist && file_exists($this->targetDir . $fileName)) {
                $fileName = $this->generateUniqueFileName($fileName);
            }

            $targetFilePath = $this->targetDir . $fileName;

            if (!$this->renameIfExist && file_exists($targetFilePath)) {
                throw new Exception($this->trans("A file with this name already exists."));
            }

            if (!move_uploaded_file($fileTempName, $targetFilePath)) {
                throw new Exception($this->trans("Image upload failed! Try again."));
            }

            return [
                'status' => 'success',
                'message' => $this->trans("Image uploaded successfully."),
                'data' => $fileName,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'danger',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function setTargetDir(string $targetDir): void
    {
        $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->validateDirectory($targetDir);
        $this->targetDir = $targetDir;
    }

    public function setAllowedTypes(array $allowedTypes): void
    {
        $this->allowedTypes = array_filter($allowedTypes, 'is_string');
    }

    public function setMaxFileSize(int $maxFileSize): void
    {
        if ($maxFileSize > 0) {
            $this->maxFileSize = $maxFileSize;
        }
    }

    public function setMaxWidth(?int $maxWidth): void
    {
        $this->maxWidth = $maxWidth;
    }

    public function setMaxHeight(?int $maxHeight): void
    {
        $this->maxHeight = $maxHeight;
    }

    public function setRenameIfExist(bool $rename): void
    {
        $this->renameIfExist = $rename;
    }

    public function setTranslator(array $translations, ?string $lang = null): void
    {
        $this->translations = $translations;

        $lang = strtolower($lang);
        if (($lang !== null) && array_key_exists($lang, $translations)) {
            $this->lang = $lang;
        }
    }

    private function validateDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new Exception($this->trans("Failed to create directory: $directory"));
        }
    }

    private function validateFileSize($size): void
    {
        if ($size > $this->maxFileSize) {
            throw new Exception($this->trans('File exceeds the maximum allowed size of ' . $this->formatFileSize($this->maxFileSize)));
        }
    }

    private function validateAllowedTypes(string $imageExt): void
    {
        if (!in_array($imageExt, $this->allowedTypes)) {
            throw new Exception($this->trans('Allowed extensions are: ' . implode(', ', $this->allowedTypes)));
        }
    }

    private function validateImageDimensions(string $filePath): void
    {
        $imageInfo = getimagesize($filePath);

        if ($imageInfo === false) {
            throw new Exception($this->trans("Uploaded file is not a valid image."));
        }
        if ($this->maxWidth && $imageInfo[0] > $this->maxWidth) {
            throw new Exception($this->trans("Image width exceeds the maximum allowed width of {$this->maxWidth} pixels."));
        }
        if ($this->maxHeight && $imageInfo[1] > $this->maxHeight) {
            throw new Exception($this->trans("Image height exceeds the maximum allowed height of {$this->maxHeight} pixels."));
        }
    }

    private function generateUniqueFileName(string $fileName): string
    {
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileBaseName = strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        if (file_exists($this->targetDir . $fileName)) {
            return $fileBaseName . '_' . uniqid() . '.' . $fileExtension;
        }

        return $fileName;
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileBaseName = strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        $fileBaseName = preg_replace('/[^a-z0-9-_]/', '_', $fileBaseName);
        $fileBaseName = preg_replace('/_+/', '_', $fileBaseName);

        $fileBaseName = substr($fileBaseName, 0, 100);

        return $fileBaseName . '.' . $fileExtension;
    }

    private function formatFileSize(int $bytes): string
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $size[$factor]);
    }

    private function trans(string $key): string
    {
        return $this->translations[$this->lang][$key] ?? $key;
    }
}
