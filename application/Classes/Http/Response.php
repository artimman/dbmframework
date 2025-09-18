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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private int $statusCode;
    private array $headers;
    private ?StreamInterface $body;
    private string $reasonPhrase = '';

    public function __construct(int $statusCode = 200, array $headers = [], ?StreamInterface $body = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->reasonPhrase = $this->getDefaultReasonPhrase($statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($code);
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /** ### Class extension ### */
    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        return $clone;
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
        $header = $this->headers[$name] ?? [];
        return is_array($header) ? $header : [$header];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = [$value];
        return $clone;
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $clone = clone $this;

        if (!isset($clone->headers[$name])) {
            $clone->headers[$name] = [];
        }

        $clone->headers[$name][] = $value;
        return $clone;
    }

    public function withoutHeader(string $name): self
    {
        $clone = clone $this;
        unset($clone->headers[$name]);
        return $clone;
    }

    public function getBody(): ?StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $values) {
            if (is_array($values)) {
                foreach ($values as $value) {
                    header("$name: $value", false);
                }
            } else {
                header("$name: $values", false);
            }
        }

        if ($this->body) {
            echo $this->body;
        }
    }

    /**
     * Odpowiedź HTML
     *
     * TODO! Nie używane: $content = $this->render('index.phtml', []); return Response::html($content); ?
     */
    public static function html(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $headers = array_merge($headers, ['Content-Type' => 'text/html']);
        return new self($statusCode, $headers, new Stream($content));
    }

    /**
     * Odpowiedź Json.
     *
     * Przykład użycia: return Response::json(['success' => true, 'message' => "Komunikat!"]);
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $stream = new Stream($json);
        return new self($statusCode, ['Content-Type' => 'application/json'], $stream);
    }

    /**
     * Debugowanie.
     *
     * $response = Response::json(['success' => true, 'message' => 'Test'], 200);
     * $response->debug();
     */
    public function debug(): void
    {
        echo "Status Code: {$this->statusCode}\n";
        echo "Headers:\n";

        foreach ($this->headers as $name => $values) {
            echo "$name: " . implode(', ', $values) . "\n";
        }

        echo "Body:\n{$this->body}\n";

        exit;
    }

    /** ### ADDED: Rozszerzenie funkcjonalności ### */
    private function getDefaultReasonPhrase(int $code): string
    {
        return match ($code) {
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            default => '',
        };
    }
}
