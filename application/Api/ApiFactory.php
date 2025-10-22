<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * Example of use:
 * $this->api = ApiFactory::create($baseUrl, $config['token'] ?? null);
 */

declare(strict_types=1);

namespace Dbm\Api;

use InvalidArgumentException;

class ApiFactory
{
    public static function create(string $baseUrl, ?string $token = null): ApiClientInterface
    {
        $driver = strtolower(getenv('API_CLIENT_DRIVER'));

        return match ($driver) {
            'guzzle' => new ApiGuzzleClient($baseUrl, $token),
            'native' => new ApiClient($baseUrl, $token),
            'auto' => class_exists(\GuzzleHttp\Client::class)
                ? new ApiGuzzleClient($baseUrl, $token)
                : new ApiClient($baseUrl, $token),
            default => throw new InvalidArgumentException("Unknown API_CLIENT_DRIVER: {$driver}"),
        };
    }
}
