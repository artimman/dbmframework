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

namespace Dbm\Middleware;

use Dbm\Api\ApiJwtService;
use Dbm\Classes\Log\Logger;
use Psr\Http\Message\RequestInterface;

/**
 * Middleware logujący każde żądanie HTTP.
 *
 * - Loguje metodę, ścieżkę i IP klienta.
 * - Może być rozszerzony o logowanie czasu wykonania requestu.
 */
class RequestLoggerMiddleware
{
    private Logger $logger;
    private ApiJwtService $jwtService;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->jwtService = new ApiJwtService();
    }

    /**
     * @param RequestInterface $request
     * @return null
     */
    public function __invoke(RequestInterface $request): ?array
    {
        /** @var \Dbm\Classes\Http\Request|RequestInterface $request */
        $server = $request->getServerParams();
        $ip = $request->getClientIp() ?? ($server['REMOTE_ADDR'] ?? 'unknown');
        $method = $request->getMethod() ?: ($server['REQUEST_METHOD'] ?? 'GET');
        $uri = $request->getUri()?->getPath() ?? ($serverParams['REQUEST_URI'] ?? '/');

        $payload = $this->jwtService->decodeToken($token ?? '');
        $userRole = $payload['role'] ?? 'guest';

        $end = microtime(true);
        $duration = defined('REQUEST_START_TIME')
            ? round(($end - REQUEST_START_TIME) * 1000, 2) : 0.0; // ms

        $this->logger->info(
            "Middleware Request {method} {uri} by {role} from {ip} in {time} ms",
            [
                'method' => $method,
                'uri' => $uri,
                'ip' => $ip,
                'role' => $userRole,
                'time' => $duration,
            ]
        );

        return null;
    }
}
