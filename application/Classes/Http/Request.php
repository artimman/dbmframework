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
 * Concrete implementation of ExtendedRequestInterface.
 * Provides extended PSR-7 compliant HTTP request handling.
 *
 * ────────────────────────────────────────────────
 * REQUEST USAGE GUIDE (DbM Framework)
 * ────────────────────────────────────────────────
 *
 * getParsedBody(): ?array
 * ───────────────────────────
 * Standard PSR-7 method. Safely parses and returns request body
 * for HTML forms, JSON requests, and multipart uploads.
 *
 * Supports:
 *  - application/x-www-form-urlencoded (HTML form)
 *  - application/json (API requests)
 *  - multipart/form-data (file upload)
 *
 * Example:
 *   $data = $request->getParsedBody();
 *   $email = $data['email'] ?? null;
 *
 * Used in:
 *   Controllers, Services, API endpoints
 *
 * ────────────────────────────────────────────────
 * getAllPost(): array
 * ───────────────────────────
 * Shortcut for accessing sanitized $_POST.
 * Does not parse raw body or JSON.
 *
 * Example:
 *   $data = $request->getAllPost();
 *
 * Used in:
 *   Internal legacy modules, form helpers, BaseController
 *
 * ────────────────────────────────────────────────
 * getAllQuery(): array
 * ───────────────────────────
 * Returns $_GET parameters (query string).
 *
 * Example:
 *   $page = (int) ($request->getAllQuery()['page'] ?? 1);
 *
 * Used in:
 *   Pagination, filters, search forms
 *
 * ────────────────────────────────────────────────
 * getBody(): StreamInterface
 * ───────────────────────────
 * Returns raw request body as stream.
 *
 * Example:
 *   $json = $request->getBody()->__toString();
 *
 * Used in:
 *   Debugging, logging, file uploads
 *
 * ────────────────────────────────────────────────
 * getHeaders() / hasHeader() / getHeaderLine()
 * ───────────────────────────
 * Access to HTTP headers.
 *
 * Example:
 *   $contentType = $request->getHeaderLine('Content-Type');
 *
 * ────────────────────────────────────────────────
 * getServerParams(): array
 * ───────────────────────────
 * Returns $_SERVER variables (scheme, host, method, etc.)
 *
 * ────────────────────────────────────────────────
 * getUploadedFiles(): array
 * ───────────────────────────
 * Returns $_FILES information for multipart requests.
 *
 * ────────────────────────────────────────────────
 * Summary:
 *  - Controllers:       → getParsedBody()
 *  - Form processors:   → getParsedBody() / getAllPost()
 *  - Search pages:      → getAllQuery()
 *  - Low-level tools:   → getBody(), getHeaders()
 */

declare(strict_types=1);

namespace Dbm\Classes\Http;

use Dbm\Psr\Http\Message\ExtendedRequestInterface;
use Psr\Http\Message\UriInterface;
use Exception;
use JsonException;
use SimpleXMLElement;

/**
 * Class Request
 *
 * Implements the ExtendedRequestInterface, providing convenient
 * access to HTTP request data (headers, query params, POST, JSON,
 * files, and client/server information). Fully compatible with PSR-7.
 */
class Request extends Message implements ExtendedRequestInterface
{
    /** @var UriInterface */
    private UriInterface $uri;

    /** @var array Custom route or application parameters */
    private array $params = [];

    /** @var array $_GET parameters */
    private array $queryParams = [];

    /** @var array $_POST parameters */
    private array $postParams = [];

    /** @var array $_FILES parameters */
    private array $filesParams = [];

    /** @var string HTTP request method */
    private string $method;

    /**
     * Constructs a new Request from PHP globals.
     *
     * Automatically reads headers, body, query, post, and files.
     */
    public function __construct()
    {
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];

        try {
            $this->body = new Stream(file_get_contents('php://input') ?: '');
        } catch (Exception $e) {
            $this->body = new Stream('');
        }

        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->filesParams = $_FILES;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Build URI from globals
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : null;
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '';

        $this->uri = new Uri($path, $scheme, $host, $port, $query);
    }

    /**
     * Returns the request target (path component).
     */
    public function getRequestTarget(): string
    {
        return $this->uri->getPath();
    }

    /**
     * Returns a new instance with the provided request target.
     */
    public function withRequestTarget(string $requestTarget): static
    {
        $new = clone $this;
        $new->uri = $new->uri->withPath($requestTarget);
        return $new;
    }

    /**
     * Returns HTTP method (GET, POST, etc.).
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns a new instance with the provided HTTP method.
     */
    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    /**
     * Returns the request URI.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns a new instance with the provided URI.
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost) {
            $new->headers['Host'] = [$uri->getHost()];
        }

        return $new;
    }

    // --- ADDED methods - Extended Functionality ---

    /** @inheritdoc */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /** @inheritdoc */
    public function setQueryParams(array $queryParams): void
    {
        $this->queryParams = $queryParams;
    }

    /** @inheritdoc */
    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /** @inheritdoc */
    public function getParsedBody(): ?array
    {
        $contentType = strtolower($this->getContentType() ?? '');
        $bodyContent = trim($this->body->__toString());

        if ($bodyContent === '' && !empty($this->postParams)) {
            return $this->postParams;
        }

        if (str_contains($contentType, 'application/json')) {
            try {
                $decoded = json_decode($bodyContent, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : null;
            } catch (JsonException) {
                return null; // Invalid JSON
            }
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            if ($bodyContent !== '') {
                parse_str($bodyContent, $parsed);
                return is_array($parsed) ? $parsed : null;
            }
            return $this->postParams ?: null;
        }

        if (str_contains($contentType, 'multipart/form-data')) {
            return !empty($this->postParams) ? $this->postParams : ($_POST ?: null);
        }

        return !empty($this->postParams) ? $this->postParams : null;
    }

    /** @inheritdoc */
    public function hasParsedBody(): bool
    {
        $parsedBody = $this->getParsedBody();
        return !empty($parsedBody);
    }

    /** @inheritdoc */
    public function getJsonBody(): ?array
    {
        return $this->isJson() ? $this->getParsedBody() : null;
    }

    /** @inheritdoc */
    public function getXmlBody(): ?SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($this->body->__toString(), SimpleXMLElement::class, LIBXML_NOENT | LIBXML_NOCDATA);
        libxml_clear_errors();
        return $xml ?: null;
    }

    /** @inheritdoc */
    public function getContentType(): ?string
    {
        $header = $this->headers['Content-Type'] ?? null;
        return is_array($header) ? $header[0] : $header;
    }

    /** @inheritdoc */
    public function getAuthorizationHeader(): ?string
    {
        return $this->headers['Authorization'][0] ?? null;
    }

    /** @inheritdoc */
    public function isJson(): bool
    {
        return strpos($this->getContentType() ?? '', 'application/json') !== false;
    }

    /** @inheritdoc */
    public function isFormUrlEncoded(): bool
    {
        return strpos($this->getContentType() ?? '', 'application/x-www-form-urlencoded') !== false;
    }

    /** @inheritdoc */
    public function getClientIp(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /** @inheritdoc */
    public function getClientPort(): ?int
    {
        return isset($_SERVER['REMOTE_PORT']) ? (int) $_SERVER['REMOTE_PORT'] : null;
    }

    /** @inheritdoc */
    public function getServerParams(): array
    {
        return [
            'PHP_SELF' => $_SERVER['PHP_SELF'] ?? null,
            'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? null,
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? null,
            'HTTPS' => $_SERVER['HTTPS'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? null,
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
            'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? null,
        ];
    }

    /** @inheritdoc */
    public function getPutParams(): ?array
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            parse_str($this->body->__toString(), $putParams);
            return $putParams;
        }
        return null;
    }

    /** @inheritdoc */
    public function getPreferredLanguage(array $availableLanguages): ?string
    {
        $acceptLanguage = $this->headers['Accept-Language'][0] ?? '';

        if (!$acceptLanguage) {
            return null;
        }

        foreach (explode(',', $acceptLanguage) as $lang) {
            $lang = trim(strtolower(explode(';', $lang)[0]));
            foreach ($availableLanguages as $available) {
                if (str_starts_with($lang, strtolower($available))) {
                    return $available;
                }
            }
        }

        return null;
    }

    /** @inheritdoc */
    public function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /** @inheritdoc */
    public function getReferer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /** @inheritdoc */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    }

    // --- ADDED methods partially compliant with PSR ---

    /** @inheritdoc */
    public function getUploadedFiles(): array
    {
        return $this->filesParams;
    }

    /** @inheritdoc */
    public function getUploadedFile(string $key): ?array
    {
        return $this->filesParams[$key] ?? null;
    }

    // --- ADDED Framework methods ---

    /** @inheritdoc */
    public function getQuery(string $key, $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /** @inheritdoc */
    public function getPost(string $key, $default = null): mixed
    {
        $data = $this->getParsedBody();

        return $data[$key] ?? $default;
    }

    /** @inheritdoc */
    public function getAllQuery(): array
    {
        return $this->queryParams;
    }

    /** @inheritdoc */
    public function getAllPost(): array
    {
        return $this->postParams;
    }

    /** @inheritdoc */
    public function get(string $key, $default = null): mixed
    {
        $postValue = $this->getPost($key);
        return $postValue !== null ? $postValue : $this->getQuery($key, $default);
    }

    /** @inheritdoc */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /** @inheritdoc */
    public function getParam(string $key): ?string
    {
        return $this->params[$key] ?? null;
    }

    /** @inheritdoc */
    public function getParams(): array
    {
        return $this->params;
    }

    /** @inheritdoc */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /** @inheritdoc */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /** @inheritdoc */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /** @inheritdoc */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /** @inheritdoc */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /** @inheritdoc */
    public static function fromGlobals(): static
    {
        return new self();
    }

    /** @inheritdoc */
    public static function capture(): static
    {
        return self::fromGlobals();
    }
}
