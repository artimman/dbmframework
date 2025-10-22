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

use Psr\Http\Message\RequestInterface;

/**
 * Simple CORS middleware.
 *
 * - Ustawia nagłówki CORS.
 * - Obsługuje preflight (OPTIONS) odpowiedzią 204 i kończy request.
 *
 * TODO! Klasy można rozszerzyć oraz napisać zgodnie z PSR, zamienić / dodać RequestInterface -> ServerRequestInterface {} ?
 * PSR-15 kompatybilność: Middleware to klasa implementująca MiddlewareInterface (process($request, RequestHandlerInterface $handler)).
 * Wymaga RequestHandlerInterface i ResponseInterface.
 */
class CorsMiddleware
{
    /**
     * @param \Dbm\Classes\Http\Response|RequestInterface $request
     * @return null|mixed  // null = continue, otherwise router may short-circuit
     */
    public function __invoke(RequestInterface $request)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        // If it's a preflight request, respond immediately.
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            http_response_code(204);
            // minimal body for preflight -> end execution
            exit;
        }

        return null;
    }
}
