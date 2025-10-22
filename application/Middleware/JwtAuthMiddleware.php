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
use Dbm\Classes\Http\Response;
use Psr\Http\Message\RequestInterface;

class JwtAuthMiddleware
{
    private ApiJwtService $jwtService;

    public function __construct()
    {
        $this->jwtService = new ApiJwtService();
    }

    public function __invoke(RequestInterface $request): ?array
    {
        /** @var \Dbm\Classes\Http\Request|RequestInterface $request */
        $auth = $request->getAuthorizationHeader();

        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            Response::json(['error' => 'Missing token'], 401);
            exit;
        }

        $token = trim(substr($auth, 7));
        if (!$this->jwtService->validateToken($token)) {
            Response::json(['error' => 'Invalid token'], 401);
            exit;
        }

        return null;
    }
}
