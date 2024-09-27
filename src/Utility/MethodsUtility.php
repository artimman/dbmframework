<?php
/*
 * DbM Framework
 * All code copyright Design by Malina
 * DbM: www.dbm.org.pl
 */

declare(strict_types=1);

namespace App\Utility;

class MethodsUtility
{
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

    public function scanDirectory(string $directory, int $sort = 0, array $arraySkip = ['..', '.']): ?array
    {
        if (is_dir($directory)) {
            return array_diff(scandir($directory, $sort), $arraySkip);
        }

        return null;
    }
}
