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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

/**
 * HTTP client based on Guzzle - works with Composer and is compatible with ApiClient
 */
class ApiGuzzleClient implements ApiClientInterface
{
    private Client $client;
    private Logger $logger;
    private ?string $token;

    public function __construct(string $baseUrl, ?string $jwtToken = null)
    {
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => 15.0,
            'http_errors' => false,
        ]);

        $this->token = $jwtToken;
        $this->logger = new Logger();
    }

    public function request(string $method, string $endpoint, array $options = []): ApiResponse
    {
        $headers = $options['headers'] ?? [];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        $options['headers'] = $headers;

        $start = microtime(true);

        try {
            $response = $this->client->request($method, ltrim($endpoint, '/'), $options);
            $duration = round((microtime(true) - $start) * 1000, 2);

            $this->logger->info(
                "API Request {method} {endpoint} => {status} in {time} ms",
                [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->getStatusCode(),
                    'time' => $duration
                ],
                'requests_api'
            );

            return ApiResponse::fromGuzzle($response);
        } catch (RequestException $e) {
            throw new ApiException("API Request failed: " . $e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new ApiException("Unexpected API error: " . $e->getMessage(), 500, $e);
        }
    }

    public function get(string $endpoint, array $query = []): ApiResponse
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
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
