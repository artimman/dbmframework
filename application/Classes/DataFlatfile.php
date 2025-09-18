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

use App\Config\ConstantConfig;
use Dbm\Classes\Dto\FileOperationDto;
use Dbm\Classes\Exceptions\NotFoundException;
use Dbm\Classes\Helpers\LanguageHelper;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Logs\Logger;
use Dbm\Classes\Managers\CookieManager;
use Dbm\Interfaces\DataFlatfileInterface;
use Exception;

class DataFlatfile implements DataFlatfileInterface
{
    private Logger $logger;
    private LanguageHelper $language;
    private CookieManager $cookie;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->language = new LanguageHelper();
        $this->cookie = new CookieManager();
    }

    public function dataFlatFile(string $type = 'content', string $space = '', ?string $path = null): FileOperationDto
    {
        $arrKeys = ['keywords', 'description', 'title', 'content'];
        $file = $this->fileName() . '.txt';

        if (!isset($path)) {
            $path = ConstantConfig::PATH_DATA_CONTENT;
        }

        $path = $path . $file;
        $space = is_numeric($space) ? str_repeat('    ', (int)$space) : $space ?? '';

        if (!file_exists($path) || filesize($path) <= 0) {
            $errorMessage = "Page not found! File does not exist or is empty: {$file}.";
            throw new NotFoundException($errorMessage);
        }

        try {
            $txtHtml = trim(file_get_contents($path));
            $arrayData = $this->arrayFillKeys($arrKeys, explode('<!--@-->', $txtHtml));

            if (!isset($arrayData[$type]) || empty($arrayData[$type])) {
                $errorMessage = "Invalid data format or missing key '{$type}' in file.";
                $this->logger->warning($errorMessage);
                throw new NotFoundException($errorMessage);
            }

            $data = $type === 'content'
                ? $this->replaceContent($arrayData[$type], $space)
                : strip_tags(trim($arrayData[$type]));

            return new FileOperationDto(true, $data, null);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            throw new NotFoundException("An error occurred while processing the file.");
        }
    }

    public function fileName(): string
    {
        $divider = '.';
        $request = new Request();
        $serverParams = $request->getServerParams();
        $dir = dirname($serverParams['PHP_SELF'] ?? '');
        $uri = $serverParams['REQUEST_URI'] ?? '';

        $parsedUrl = parse_url($uri);
        $path = $parsedUrl['path'] ?? '';
        $query = $parsedUrl['query'] ?? '';

        parse_str($query, $queryParams);
        $lang = $queryParams['lang'] ?? $this->cookie->getCookie('dbmLanguage');
        $availableLanguages = array_map('strtolower', $this->language->getAvailableLanguages());

        if (!is_string($lang) || trim($lang) === '' || !in_array(strtolower($lang), $availableLanguages, true)) {
            $lang = $this->language->getDefaultLanguage();
            $lang = is_string($lang) ? strtolower($lang) : 'err';
        } else {
            $lang = strtolower($lang);
        }

        if (strpos($dir, 'public') !== false) {
            $publicPath = substr($dir, 0, strpos($dir, 'public'));
            $path = str_replace($publicPath, '', $path);
        }

        $name = ltrim($path, '/');
        $name = str_replace(['/', '.html'], ['-', ''], $name);

        if (strpos($name, $divider) !== false) {
            $name = substr($name, 0, strpos($name, $divider));
            $name = 'page-' . $name;
        }

        return $lang . '_' . $name;
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

    private function replaceContent(string $content, string $space = ''): string
    {
        $space = is_numeric($space) ? str_repeat('    ', (int)$space) : $space ?? '';
        $search = ["\n", "[URL]"];
        $replace = ["\n" . $space, getenv('APP_URL')];

        return trim(str_replace($search, $replace, $content)) . "\n";
    }

    private function getAvailablePages(): array
    {
        $lang = strtolower($this->language->getDefaultLanguage());
        $pattern = ConstantConfig::PATH_DATA_CONTENT . "{$lang}_*.txt";
        $files = glob($pattern) ?: [];

        return array_reduce($files, function ($pages, $file) use ($lang) {
            $slug = str_replace("{$lang}_", '', basename($file, ".txt"));
            $pages[$slug] = ucwords(str_replace('-', ' ', $slug));
            return $pages;
        }, []);
    }
}
