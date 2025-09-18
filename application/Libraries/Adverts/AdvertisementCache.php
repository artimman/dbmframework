<?php
/**
 * Library: On-Page Advertising Handling System
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @package Lib\AdvertisementCache
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Lib\Adverts;

class AdvertisementCache
{
    private static $instance;
    private const ADS_DIR = BASE_DIRECTORY . "data" . DS . "adverts";
    private const CACHE_FILE = BASE_DIRECTORY . "var" . DS . "cache" . DS . "adverts" . DS . "ads_cache.php";

    private $cache = [];

    public function __construct()
    {
        $this->preloadAds();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getAdvert(string $position, string $space = ''): string
    {
        $advert = $this->cache[$position] ?? '';

        $needle = '[URL]';

        if (!empty($advert) && strpos($advert, $needle) !== false) {
            $space = is_numeric($space) ? str_repeat('    ', (int)$space) : $space ?? '';
            $search = [PHP_EOL, $needle];
            $replace = [PHP_EOL . $space, getenv('APP_URL')];

            return trim(str_replace($search, $replace, $advert)) . PHP_EOL;
        }

        return $advert;
    }

    private function preloadAds(): void
    {
        if ($this->isCacheValid()) {
            $this->cache = include self::CACHE_FILE;
            return;
        }

        $this->cache = $this->loadAdsFromFiles();
        $this->saveCache();
    }

    private function isCacheValid(): bool
    {
        if (!file_exists(self::CACHE_FILE)) {
            return false;
        }

        $cacheTime = filemtime(self::CACHE_FILE);

        foreach (glob(self::ADS_DIR . DS . "*.txt") as $filePath) {
            if (filemtime($filePath) > $cacheTime) {
                return false;
            }
        }

        return true;
    }

    private function loadAdsFromFiles(): array
    {
        $ads = [];

        foreach (glob(self::ADS_DIR . DS . "*.txt") as $filePath) {
            $position = basename($filePath, '.txt');
            $ads[$position] = file_get_contents($filePath) ?: '';
        }

        return $ads;
    }

    private function saveCache(): void
    {
        $cacheDir = dirname(self::CACHE_FILE);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents(self::CACHE_FILE, "<?php return " . var_export($this->cache, true) . ";", LOCK_EX);
    }
}
