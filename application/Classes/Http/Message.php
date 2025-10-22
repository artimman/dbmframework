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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

class Message implements MessageInterface
{
    protected string $protocolVersion = '1.1';
    protected array $headers = [];
    protected ?StreamInterface $body = null;

    public function __construct(?StreamInterface $body = null, array $headers = [])
    {
        $this->body = $body;
        $this->headers = $headers;
    }

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
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return true;
            }
        }
        return false;
    }

    public function getHeader(string $name): array
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return (array) $value;
            }
        }
        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $this->assertHeaderName($name);
        $this->assertHeaderValue($value);

        $new = clone $this;
        $new->headers[$name] = (array) $value;
        return $new;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $this->assertHeaderName($name);
        $this->assertHeaderValue($value);

        $new = clone $this;
        $values = $this->getHeader($name);
        $new->headers[$name] = array_merge($values, (array) $value);
        return $new;
    }

    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        unset($new->headers[$name]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    protected function assertHeaderName(string $name): void
    {
        if ($name === '' || preg_match('/[^a-zA-Z0-9\'`#$%&*+.^_|~!-]/', $name)) {
            throw new InvalidArgumentException("Invalid header name: {$name}");
        }
    }

    protected function assertHeaderValue($value): void
    {
        if (!is_string($value) && !is_array($value)) {
            throw new InvalidArgumentException('Header value must be string or array');
        }
    }
}
