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

use Dbm\Psr\Http\Message\ExtendedResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A class representing an HTTP response compliant with PSR-7 (ResponseInterface),
 * extended with DbM framework functions.
 */
class Response extends Message implements ExtendedResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase = '';

    /**
     * Constructs a new Response
     *
     * @param int                   $statusCode Kod statusu HTTP.
     * @param array<string, string> $headers    Tablica nagłówków.
     * @param StreamInterface|null  $body       Treść odpowiedzi (stream).
     */
    public function __construct(int $statusCode = 200, array $headers = [], ?StreamInterface $body = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->reasonPhrase = $this->getDefaultReasonPhrase($statusCode);
    }

    /** {@inheritdoc} */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** {@inheritdoc} */
    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($code);
        return $clone;
    }

    /** {@inheritdoc} */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    // --- ADDED methods - Extended Functionality ---

    /** {@inheritdoc} */
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
            echo (string) $this->body;
        }
    }

    /** {@inheritdoc} */
    public function debug(): void
    {
        echo "Status Code: {$this->statusCode}\n";
        echo "Headers:\n";

        foreach ($this->headers as $name => $values) {
            echo "$name: " . implode(', ', (array) $values) . "\n";
        }

        echo "Body:\n" . (string) $this->body . "\n";
        exit;
    }

    /** {@inheritdoc} */
    public static function html(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $headers = array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers);
        return new self($statusCode, $headers, new Stream($content));
    }

    /** {@inheritdoc} */
    public static function json(array $data, int $statusCode = 200): static
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $stream = new Stream($json);
        return new self($statusCode, ['Content-Type' => 'application/json; charset=UTF-8'], $stream);
    }

    /**
     * Zwraca domyślną nazwę statusu HTTP (reason phrase).
     *
     * @param int $code
     * @return string
     */
    private function getDefaultReasonPhrase(int $code): string
    {
        return match ($code) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => '',
        };
    }
}
