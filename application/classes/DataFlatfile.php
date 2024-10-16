<?php
/*
 * Application: DbM Framework version 2
 * Author: Arthur Malinowski (C) Design by Malina
 * Web page: www.dbm.org.pl
 * License: MIT
*/

declare(strict_types=1);

namespace Dbm\Classes;

use Dbm\Interfaces\DataFlatfileInterface;

class DataFlatfile implements DataFlatfileInterface
{
    public function dataFlatFile(string $type = 'content', string $sign = ''): string
    {
        $path = BASE_DIRECTORY . 'data' . DS . 'content' . DS . $this->fileName();
        $arrKeys = array('keywords', 'description', 'title', 'content');
        $result = 'error' . ucfirst($type);

        if (file_exists($path) && (filesize($path) > 0)) {
            $file = fopen($path, "r");
            $txtHtml = fread($file, filesize($path));
            fclose($file);

            $txtHtml = trim($txtHtml);
            $arrayData = explode('<!--@-->', $txtHtml);
            $arrayData = $this->arrayFillKeys($arrKeys, $arrayData);

            if (!empty($arrayData[$type])) {
                if ($type === $arrKeys[3]) {
                    $result = $this->replaceContent($arrayData[$type], $sign);
                } else {
                    $result = strip_tags(trim($arrayData[$type]));
                }
            } elseif ($type === $arrKeys[3]) {
                $result = '<div class="alert alert-danger">ERROR: The page content could not be loaded! The file contains invalid data format.</div>' . "\n";
            }
        } elseif ($type === $arrKeys[3]) {
            $result = '<div class="alert alert-danger">ERROR: File does not exist or is empty, check path ' . $path . '</div>' . "\n";
        }

        return $result;
    }

    private function fileName(): string
    {
        $divider = '.';
        $dir = dirname($_SERVER['PHP_SELF']);
        $name = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);

        if (strpos($dir, 'public')) {
            $path = substr($dir, 0, strpos($dir, 'public'));
            $name = str_replace($path, '', $name);
        }

        $name = ltrim($name, '/');
        $name = str_replace(['/', '.html'], ['-', ''], $name);

        if (strpos($name, $divider) !== false) {
            $name = substr($name, 0, strpos($name, $divider));
            $name = 'page-' . $name;
        }

        return $name . '.txt';
    }

    private function arrayFillKeys(array $arrayKeys, array $arrayValues): ?array
    {
        if (!is_array($arrayKeys)) {
            return null;
        }

        $arrayFilled = [];

        foreach ($arrayKeys as $key => $value) {
            if (array_key_exists($key, $arrayValues)) {
                $arrayFilled[$value] = $arrayValues[$key];
            }
        }

        return $arrayFilled;
    }

    private function replaceContent(string $content, string $sign = ''): string
    {
        $search = array("\n", "[URL]");
        $replace = array("\n" . $sign, getenv('APP_URL'));

        return trim(str_replace($search, $replace, $content)) . "\n";
    }
}
