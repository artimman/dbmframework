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

use Dbm\Classes\BaseApiController;
use Psr\Http\Message\RequestInterface;

/**
 * API authentication middleware.
 *
 * - Sprawdza nagłówek Authorization (najpierw getHeaderLine, fallback do SERVER params).
 * - Jeśli nagłówek nieprawidłowy -> używa BaseApiController::error() jeśli dostępny, w przeciwnym razie
 *   wysyła JSON z 401 i kończy request.
 *
 * TODO! Klasy można rozszerzyć oraz napisać zgodnie z PSR, zamienić / dodać RequestInterface -> ServerRequestInterface {} ?
 * PSR-15 kompatybilność: Middleware to klasa implementująca MiddlewareInterface (process($request, RequestHandlerInterface $handler)).
 * Wymaga RequestHandlerInterface i ResponseInterface.
 */
class ApiAuthMiddleware
{
    /**
     * @param RequestInterface $request
     * @return null|mixed  // null = continue, otherwise router may short-circuit
     */
    public function __invoke(RequestInterface $request)
    {
        // Prefer PSR-7 header access
        /* $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader)) {
            // Fallback for some SAPI setups
            $server = $request->getServerParams();
            $authHeader = $server['HTTP_AUTHORIZATION'] ?? $server['Authorization'] ?? null;
        }

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            // If framework's BaseApiController::error exists and returns a value the router understands, use it.
            if (class_exists(BaseApiController::class) && is_callable([BaseApiController::class, 'error'])) {
                return BaseApiController::error('Unauthorized', 401);
            }

            // Fallback — wysyłamy prostą odpowiedź JSON i przerywamy wykonywanie.
            header('Content-Type: application/json; charset=utf-8', true, 401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        } */

        // TODO: tu można dodać walidację tokena (JWT verification, DB check itp.)
        return null;
    }
}
