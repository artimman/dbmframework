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
 * PSR-7: Extended Response Interface
 * ----------------------------------
 * Extension of the PSR-7 (ResponseInterface) standard with additional methods
 * useful in the DbM framework, such as sending responses, debugging
 * and quickly generating JSON or HTML responses.
 */

declare(strict_types=1);

namespace Dbm\Psr\Http\Message;

use Dbm\Classes\Http\Response;
use Psr\Http\Message\ResponseInterface;

interface ExtendedResponseInterface extends ResponseInterface
{
    /**
     * Sends an HTTP response to the browser.
     * Includes setting the status code, headers, and body text.
     *
     * @return void
     */
    public function send(): void;

    /**
     * Displays detailed response information (status, headers, body)
     * and terminates the script. Helpful for debugging.
     *
     * @return void
     */
    public function debug(): void;

    /**
     * Creates a new HTML response.
     *
     * @param string $content   Treść HTML.
     * @param int    $statusCode Kod HTTP (domyślnie 200).
     * @param array  $headers    Dodatkowe nagłówki.
     * @return Response
     */
    public static function html(string $content, int $statusCode = 200, array $headers = []): Response;

    /**
     * Creates a new JSON response.
     *
     * @param array $data        Dane do zakodowania w JSON.
     * @param int   $statusCode  Kod HTTP (domyślnie 200).
     * @return static
     * @throws \JsonException
     */
    public static function json(array $data, int $statusCode = 200): self;
}
