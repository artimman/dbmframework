<?php
/**
 * @package Lib\Payments
 */

declare(strict_types=1);

namespace Lib\Payments;

use Dbm\Api\ApiClientInterface;
use Dbm\Api\ApiFactory;
use Dbm\Api\ApiResponse;
use Dbm\Classes\Log\Logger;

/**
 * Przelewy24 REST API client (v1)
 */
class Przelewy24Client
{
    /**
     * Secure live system URL address
     * @var string
     */
    private static string $hostSecure = 'https://secure.przelewy24.pl/';

    /**
     * Sandbox system URL address
     * @var string
     */
    private static string $hostSandbox = 'https://sandbox.przelewy24.pl/';

    /**
     * API path
     * @var string
     */
    private static string $apiVersionPath = 'api/v1/';

    /**
     * Redirect path
     * @var string
     */
    private static string $redirectPath = 'trnRequest/';

    /**
     * Use Secure (false) or Sandbox (true) enviroment
     * @var bool
     */
    private bool $envMode;

    private ApiClientInterface $client;
    private Logger $logger;
    private string $merchantId;
    private string $posId;
    private string $apiKey;

    public function __construct(
        string $merchantId,
        string $posId,
        string $apiKey,
        bool $envMode = false,
        ?ApiClientInterface $client = null
    ) {
        $this->merchantId = $merchantId;
        $this->posId = $posId;
        $this->apiKey = $apiKey;
        $this->envMode = $this->resolveEnvMode($envMode);
        $this->client = $client ?? ApiFactory::create($this->getHostForApi());
        $this->logger = new Logger();
    }

    public function registerTransaction(array $params): ApiResponse
    {
        $data = array_merge([
            'merchantId' => $this->merchantId,
            'posId'      => $this->posId,
        ], $params);

        $data['sign'] = $this->buildSign($data);
        $this->logger->info('Registering transaction', $data, 'p24');

        return $this->client->post('transaction/register', ['json' => $data]);
    }

    public function verifyTransaction(array $params): ApiResponse
    {
        $data = array_merge([
            'merchantId' => $this->merchantId,
            'posId'      => $this->posId,
        ], $params);

        $data['sign'] = $this->buildSign($data);
        $this->logger->info('Verifying transaction', $data, 'p24');

        return $this->client->put('transaction/verify', ['json' => $data]);
    }

    public function getTransactionStatus(string $sessionId): ApiResponse
    {
        return $this->client->get("transaction/by/sessionId/{$sessionId}");
    }

    public function testConnection(): ApiResponse
    {
        return $this->client->get('testAccess');
    }

    public function buildSign(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash('sha384', $json . $this->apiKey);
    }

    public function getHostForApi(): string
    {
        return ($this->envMode ? self::$hostSandbox : self::$hostSecure) . self::$apiVersionPath;
    }

    public function getHostForRedirect(): string
    {
        return ($this->envMode ? self::$hostSandbox : self::$hostSecure) . self::$redirectPath;
    }

    public function getRedirectUrl(string $token): string
    {
        $token = substr($token, 0, 100); // optional: token limit
        return $this->getHostForRedirect() . $token;
    }

    private function resolveEnvMode(bool $default): bool
    {
        $env = getenv('P24_ENV');

        if ($env === false) {
            return $default;
        }

        $env = strtolower(trim((string) $env));

        return in_array($env, ['1', 'true'], true);
    }
}
