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

namespace Dbm\Api;

use Dbm\Classes\Log\Logger;
use RuntimeException;

/**
 * Lightweight HTTP client using cURL - works without Composer and is compatible with ApiGuzzleClient
 */
class ApiClient implements ApiClientInterface
{
    private ?string $token = null;

    public function __construct(
        private string $baseUrl,
        ?string $token = null,
        private array $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: DbmHttpClient/1.0',
        ]
    ) {
        $this->token = $token;
    }

    public function request(string $method, string $endpoint, array $options = []): ApiResponse
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = $this->defaultHeaders;
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        if (!empty($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($options['json'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json']));
        } elseif (!empty($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }

        $responseBody = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("HTTP request error: {$error}");
        }

        return new ApiResponse($status, $responseBody ?: '', []);
    }

    public function get(string $endpoint, array $query = []): ApiResponse
    {
        $uri = $endpoint;
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }
        return $this->request('GET', $uri);
    }

    public function post(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('DELETE', $endpoint, ['json' => $data]);
    }
}
