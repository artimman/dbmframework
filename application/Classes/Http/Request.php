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

namespace Dbm\Classes\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use SimpleXMLElement;

class Request implements RequestInterface
{
    private UriInterface $uri;
    private array $headers = [];
    private string $protocolVersion = '1.1';
    private ?Stream $body = null;

    // ADDED
    private array $params = [];
    private array $queryParams = [];
    private array $postParams = [];
    private array $filesParams = [];
    private string $method;

    public function __construct()
    {
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];

        try {
            $this->body = new Stream(file_get_contents('php://input') ?: '');
        } catch (\Exception $e) {
            $this->body = new Stream('');
        }

        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->filesParams = $_FILES;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function getRequestTarget(): string
    {
        return $this->uri->getPath();
    }

    public function withRequestTarget(string $requestTarget): static
    {
        $new = clone $this;
        $new->uri = $new->uri->withPath($requestTarget);
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    public function getUri(): ?UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost) {
            $new->headers['Host'] = [$uri->getHost()];
        }

        return $new;
    }

    // Added for compatibility with MessageInterface
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(',', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->headers[$name] = (array) $value;
        return $new;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->headers[$name] = array_merge($this->getHeader($name), (array) $value);
        return $new;
    }

    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        unset($new->headers[$name]);
        return $new;
    }

    public function getBody(): ?StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    /** ### ADDED PSR methods - Rozszerzenie funkcjonalności ### */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function setQueryParams(array $queryParams): void
    {
        $this->queryParams = $queryParams;
    }

    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getParsedBody(): ?array
    {
        $contentType = $this->headers['Content-Type'] ?? '';

        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Użycie php://input, jeśli jest dostępne
            $bodyContent = $this->body->__toString();

            if (!empty($bodyContent)) {
                parse_str($bodyContent, $parsedBody);
                return $parsedBody;
            }

            return $this->postParams;
        }

        if (strpos($contentType, 'multipart/form-data') !== false) {
            return $this->postParams ?? $_POST;
        }

        if (strpos($contentType, 'application/json') !== false) {
            return json_decode($this->body->__toString(), true) ?? [];
        }

        return null;
    }

    public function hasParsedBody(): bool
    {
        $parsedBody = $this->getParsedBody();
        return !empty($parsedBody);
    }

    public function getJsonBody(): ?array
    {
        return $this->isJson() ? $this->getParsedBody() : null;
    }

    public function getXmlBody(): ?SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        return simplexml_load_string($this->body->__toString(), null, LIBXML_NOENT | LIBXML_NOCDATA) ?: null;
    }

    public function getContentType(): ?string
    {
        return $this->headers['Content-Type'][0] ?? $this->headers['Content-Type'] ?? null;
    }

    public function getAuthorizationHeader(): ?string
    {
        return $this->headers['Authorization'][0] ?? null;
    }

    public function isJson(): bool
    {
        return strpos($this->getContentType() ?? '', 'application/json') !== false;
    }

    public function isFormUrlEncoded(): bool
    {
        return strpos($this->getContentType() ?? '', 'application/x-www-form-urlencoded') !== false;
    }

    public function getClientIp(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function getClientPort(): ?int
    {
        return isset($_SERVER['REMOTE_PORT']) ? (int) $_SERVER['REMOTE_PORT'] : null;
    }

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
            // additional parameters
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? null,
        ];
    }

    public function getPutParams(): ?array
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            parse_str($this->body->__toString(), $putParams);
            return $putParams;
        }
        return null;
    }

    public function getPreferredLanguage(array $availableLanguages): ?string
    {
        $acceptLanguage = $this->headers['Accept-Language'][0] ?? '';
        if (!$acceptLanguage) {
            return null;
        }

        $acceptedLanguages = explode(',', $acceptLanguage);
        foreach ($acceptedLanguages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            if (in_array($lang, $availableLanguages, true)) {
                return $lang;
            }
        }
        return null;
    }

    public function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    public function getReferer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    }

    /** ### ADDED Methods partially compliant with PSR - Rozszerzenie funkcjonalności ### */
    public function getUploadedFiles(): array
    {
        return $this->filesParams;
    }

    public function getUploadedFile(string $key): ?array
    {
        return $this->filesParams[$key] ?? null;
    }

    /** ### ADDED Framework methods - Rozszerzenie funkcjonalności ### */
    public function getQuery(string $key, $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getPost(string $key, $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function getAllQuery(): array
    {
        return $this->queryParams;
    }

    public function getAllPost(): array
    {
        return $this->postParams;
    }

    public function get(string $key, $default = null): mixed
    {
        $postValue = $this->getPost($key);
        return $postValue !== null ? $postValue : $this->getQuery($key, $default);
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParam(string $key): ?string
    {
        return $this->params[$key] ?? null;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }
    /* ### ADDED End */
}
