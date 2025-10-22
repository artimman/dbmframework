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

use Psr\Http\Message\ResponseInterface;
use Exception;
use SimpleXMLElement;

class ApiResponse
{
    private int $statusCode;
    private string $body;
    private array $headers;

    public function __construct(int $statusCode, string $body, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * Umożliwia stworzenie ApiResponse bezpośrednio z odpowiedzi Guzzle.
     */
    public static function fromGuzzle(ResponseInterface $response): self
    {
        return new self(
            $response->getStatusCode(),
            (string) $response->getBody(),
            $response->getHeaders()
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return is_array($value) ? implode(', ', $value) : $value;
            }
        }

        return null;
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function json(): ?array
    {
        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function xml(): ?SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        try {
            return new SimpleXMLElement($this->body);
        } catch (Exception $e) {
            return null;
        }
    }

    public function __toString(): string
    {
        return $this->body;
    }
}
