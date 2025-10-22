<?php
/**
 * Library: DBM Http Client
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @package Lib\HttpClient
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Dbm\Api;

use RuntimeException;

/**
 * Simple Http Client - cURL core
 */
class SimpleHttpClient
{
    private array $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: DbmHttpClient/1.0'
    ];

    public function __construct(private ?string $baseUrl = null)
    {
    }

    public function request(string $method, string $uri, array $options = []): array
    {
        $url = $this->baseUrl ? rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/') : $uri;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->defaultHeaders, $options['headers'] ?? []));

        if (!empty($options['body'])) {
            $body = is_array($options['body']) ? json_encode($options['body']) : $options['body'];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("HTTP request error: {$error}");
        }

        return [
            'status' => $status,
            'body' => $response,
            'json' => json_decode($response, true)
        ];
    }
}
