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
 * PSR-7 Extended Request Interface
 *
 * This interface extends the official PSR-7 RequestInterface, providing
 * additional helper and convenience methods useful in web frameworks.
 * It preserves full PSR-7 compatibility while offering higher-level
 * abstractions for working with HTTP requests.
 */

declare(strict_types=1);

namespace Dbm\Psr\Http\Message;

use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use SimpleXMLElement;

/**
 * Interface ExtendedRequestInterface
 *
 * Extends PSR-7 RequestInterface with framework-specific features,
 * including methods for parsing query and POST parameters, content
 * detection (JSON, XML, form-data), and client environment utilities.
 */
interface ExtendedRequestInterface extends PsrRequestInterface
{
    /**
     * Returns query parameters ($_GET).
     */
    public function getQueryParams(): array;

    /**
     * Sets query parameters (useful for testing or internal overrides).
     */
    public function setQueryParams(array $queryParams): void;

    /**
     * Returns a cloned instance with the provided query parameters.
     */
    public function withQueryParams(array $query): static;

    /**
     * Returns the parsed request body as an associative array.
     *
     * The main PSR method. Used in controllers, router, API, and other base classes.
     * Handles all body parsing scenarios for APIs and web forms.
     * Compatible with JSON, x-www-form-urlencoded, and multipart/form-data.
     *
     * Automatically detects and parses:
     * - `application/json`
     * - `application/x-www-form-urlencoded`
     * - `multipart/form-data`
     *
     * Fallback: returns $this->postParams if body or Content-Type is missing.
     *
     * @return array|null Parsed request body or null if not applicable.
     */
    public function getParsedBody(): ?array;

    /**
     * Checks if the request contains a parsed body.
     */
    public function hasParsedBody(): bool;

    /**
     * Returns parsed JSON body as associative array (if available).
     */
    public function getJsonBody(): ?array;

    /**
     * Returns XML body as SimpleXMLElement (if valid XML provided).
     */
    public function getXmlBody(): ?SimpleXMLElement;

    /**
     * Returns Content-Type header value if present.
     */
    public function getContentType(): ?string;

    /**
     * Returns Authorization header value if present.
     */
    public function getAuthorizationHeader(): ?string;

    /**
     * Checks if the request body is JSON.
     */
    public function isJson(): bool;

    /**
     * Checks if the request body is form-urlencoded.
     */
    public function isFormUrlEncoded(): bool;

    /**
     * Returns client IP address.
     */
    public function getClientIp(): ?string;

    /**
     * Returns client port number.
     */
    public function getClientPort(): ?int;

    /**
     * Returns server environment parameters ($_SERVER subset).
     */
    public function getServerParams(): array;

    /**
     * Returns parsed PUT or PATCH parameters from request body.
     */
    public function getPutParams(): ?array;

    /**
     * Returns preferred language based on Accept-Language header.
     */
    public function getPreferredLanguage(array $availableLanguages): ?string;

    /**
     * Returns user-agent string.
     */
    public function getUserAgent(): ?string;

    /**
     * Returns referer URL.
     */
    public function getReferer(): ?string;

    /**
     * Determines whether the request was made over HTTPS.
     */
    public function isSecure(): bool;

    /**
     * Returns all uploaded files ($_FILES, PSR-7 compatible).
     *
     * @return array<string, array>
     */
    public function getUploadedFiles(): array;

    /**
     * Returns a single uploaded file by key, or null if missing.
     * @param string $key
     * @return array|null
     */
    public function getUploadedFile(string $key): ?array;

    /**
     * Returns a single GET (query string) parameter or default if missing.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getQuery(string $key, $default = null): mixed;

    /**
     * Returns a single POST parameter or default if missing.
     *
     * Works for both standard HTML forms and JSON bodies.
     *
     * @param string $key     The POST key to fetch
     * @param mixed  $default Default value if key is not found
     * @return mixed
     */
    public function getPost(string $key, $default = null): mixed;

    /**
     * Returns all query parameters (alias of getQueryParams()).
     */
    public function getAllQuery(): array;

    /**
     * Returns all POST parameters.
     *
     * Shortcut for accessing sanitized $_POST data.
     * For complete and content-type–aware parsing, use getParsedBody().
     */
    public function getAllPost(): array;

    /**
     * Returns a single request parameter (POST preferred, then GET).
     *
     * Equivalent to $_REQUEST[$key], but cleaner and type-safe.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null): mixed;

    /**
     * Sets custom route or framework parameters.
     */
    public function setParams(array $params): void;

    /**
     * Returns a single framework parameter.
     */
    public function getParam(string $key): ?string;

    /**
     * Returns all framework parameters.
     */
    public function getParams(): array;

    /**
     * Checks if request method matches provided one.
     */
    public function isMethod(string $method): bool;

    /**
     * Helper: check if method is GET.
     */
    public function isGet(): bool;

    /**
     * Helper: check if method is POST.
     */
    public function isPost(): bool;

    /**
     * Helper: check if method is PUT.
     */
    public function isPut(): bool;

    /**
     * Helper: check if method is DELETE.
     */
    public function isDelete(): bool;

    /**
     * Factory method that creates a Request from PHP globals.
     */
    public static function fromGlobals(): static;

    /**
     * Alias
     */
    public static function capture(): static;
}
