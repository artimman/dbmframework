<?php
/**
 * Library: Resizing uploaded images
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @package Lib\ResizeUploadImage
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * INFO: Class need extension PHP GD.
 *
 * Example usage:
 * ```php
 * $uploadImage = new ResizeUploadImage();
 *
 * $uploadImage->setMaxFileSize(); // optional: int $imageQuality
 * $uploadImage->setPhotoMaxWidth(); // optional: int $maxFileSize
 * $uploadImage->setThumbMaxWidth(); // optional: int $photoMaxWidth
 * $uploadImage->setImageQuality(); // optional: int $thumbMaxWidth
 * $uploadImage->setTranslator([
 *  'pl' => [
 *   'Invalid file upload.' => 'Nieprawidłowy plik.',
 *   // etc.
 *  ],
 * ], 'pl'); // optional
 *
 * $result = $uploadImage->createImages($_FILES['file']); // optional: string $targetDir
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

class ResizeUploadImage
{
    private const DIR_ORIGINAL = 'original/';
    private const DIR_PHOTO = 'photo/';
    private const DIR_THUMB = 'thumb/';
    private const ALLOWED_EXTENSIONS = ["jpg", "jpeg", "png", "gif", "webp"];

    private int $maxLength = 30;
    private int $minWidth = 960;
    private int $maxFileSize = 6291456;
    private int $photoMaxWidth = 1280;
    private int $thumbMaxWidth = 480;
    private int $imageQuality = 80;
    private array $translations = [];
    private string $lang = 'en';

    public function createImages(array $file, string $targetDir = 'upload/'): array
    {
        if (!isset($file['tmp_name'], $file['name'], $file['size'])) {
            return ['status' => 'danger', 'message' => $this->trans('Invalid file upload.')];
        }

        $fileTempName = $file['tmp_name'];
        $fileName = $file['name'];
        $fileSize = $file['size'];

        if ($fileSize > $this->maxFileSize) {
            return [
                'status' => 'danger',
                'message' => sprintf($this->trans('File exceeds the maximum allowed size of %d MB.'), $this->maxFileSize / (1024 * 1024))
            ];
        }

        $imageName = $this->transliteratorSanitizeFilename(pathinfo($fileName, PATHINFO_FILENAME));
        $imageExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($imageExt, self::ALLOWED_EXTENSIONS)) {
            return ['status' => 'danger', 'message' => $this->trans('Allowed extensions are: .') . implode(', .', self::ALLOWED_EXTENSIONS)];
        }

        $newFilename = $imageName.'_'.uniqid().'.'.$imageExt;
        $targetOriginal = $targetDir.self::DIR_ORIGINAL.$newFilename;
        $targetPhoto = $targetDir.self::DIR_PHOTO.$newFilename;
        $targetThumb = $targetDir.self::DIR_THUMB.$newFilename;

        try {
            if (!empty($errorCheck = $this->checkFolders($targetDir))) {
                return $errorCheck;
            }

            if (strlen($imageName) > $this->maxLength) {
                throw new Exception(sprintf($this->trans("The photo name is too long, maximum %s characters!"), $this->maxLength));
            }

            if (file_exists($targetPhoto)) {
                throw new Exception($this->trans("The file already exists! Please rename the file and try again."));
            }

            if (move_uploaded_file($fileTempName, $targetOriginal)) {
                $originalDimensions = getimagesize($targetOriginal);
                $width = $originalDimensions[0];
                $height = $originalDimensions[1];

                if ($width < $this->minWidth) {
                    unlink($targetOriginal);
                    throw new Exception(sprintf($this->trans("The uploaded file is too small. Minimum width is %s pixels."), $this->minWidth));
                }

                $this->resizeImage($targetOriginal, $targetPhoto, $imageExt, $width, $height, $this->photoMaxWidth);
                $this->resizeImage($targetOriginal, $targetThumb, $imageExt, $width, $height, $this->thumbMaxWidth, false);

                unlink($targetOriginal);

                return [
                    'status' => 'success',
                    'message' => $this->trans("Image uploaded successfully."),
                    'data' => $newFilename,
                ];
            } else {
                throw new Exception($this->trans("Image upload failed! Try again."));
            }
        } catch (Exception $e) {
            return [
                'status' => 'danger',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function setMaxFileSize(int $size): void
    {
        $this->maxFileSize = $size;
    }

    public function setPhotoMaxWidth(int $maxWidth): void
    {
        if ($maxWidth >= 960 && $maxWidth <= 1920) {
            $this->photoMaxWidth = $maxWidth;
        }
    }

    public function setThumbMaxWidth(int $maxWidth): void
    {
        if ($maxWidth >= 320 && $maxWidth <= 640) {
            $this->thumbMaxWidth = $maxWidth;
        }
    }

    public function setImageQuality(int $quality): void
    {
        if ($quality >= 50 && $quality <= 100) {
            $this->imageQuality = $quality;
        }
    }

    public function setTranslator(array $translations, ?string $lang = null): void
    {
        $this->translations = $translations;

        $lang = strtolower($lang);
        if (($lang !== null) && array_key_exists($lang, $translations)) {
            $this->lang = $lang;
        }
    }

    private function resizeImage(string $sourcePath, string $targetPath, string $extension, int $width, int $height, int $maxWidth, bool $highQuality = true): void
    {
        $aspectRatio = $width / $height;
        $newWidth = $maxWidth;
        $newHeight = (int) ($maxWidth / $aspectRatio);

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($sourcePath);
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                break;
            case 'gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new Exception($this->trans("Unsupported image format."));
        }

        if ($highQuality) {
            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        } else {
            imagecopyresized($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($resizedImage, $targetPath, $this->imageQuality);
                break;
            case 'png':
                imagepng($resizedImage, $targetPath);
                break;
            case 'gif':
                imagegif($resizedImage, $targetPath);
                break;
            case 'webp':
                imagewebp($resizedImage, $targetPath, $this->imageQuality);
                break;
        }

        imagedestroy($resizedImage);
        imagedestroy($sourceImage);
    }

    private function checkFolders(string $basePath): array
    {
        foreach ([self::DIR_ORIGINAL, self::DIR_PHOTO, self::DIR_THUMB] as $dir) {
            if (!file_exists($basePath.$dir)) {
                if (!mkdir($basePath.$dir, 0777, true)) {
                    return [
                        'status' => 'danger',
                        'message' => sprintf($this->trans("RROR! Unable to create folder %s%s."), $basePath, $dir)];
                }
            }
        }
        return [];
    }

    private function transliteratorSanitizeFilename(string $fileName): string
    {
        $safeFilename = iconv('UTF-8', 'ASCII//TRANSLIT', $fileName);
        return strtolower(preg_replace('/[^a-z0-9-]+/', '-', $safeFilename));
    }

    private function trans(string $key): string
    {
        return $this->translations[$this->lang][$key] ?? $key;
    }
}
