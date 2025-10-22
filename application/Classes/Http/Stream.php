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

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private $stream;

    public function __construct(string $content)
    {
        $this->stream = fopen('php://temp', 'r+');
        fwrite($this->stream, $content);
        rewind($this->stream);
    }

    public static function create(string $content): self
    {
        return new self($content);
    }

    public function __toString(): string
    {
        rewind($this->stream);
        return stream_get_contents($this->stream);
    }

    public function getContents(): string
    {
        return stream_get_contents($this->stream);
    }

    public function close(): void
    {
        fclose($this->stream);
    }

    public function detach()
    {
        $resource = $this->stream;
        $this->stream = null;
        return $resource;
    }

    public function getSize(): ?int
    {
        return fstat($this->stream)['size'] ?? null;
    }

    public function tell(): int
    {
        return ftell($this->stream);
    }

    public function eof(): bool
    {
        return feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        fseek($this->stream, $offset, $whence);
    }

    public function rewind(): void
    {
        rewind($this->stream);
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): int
    {
        return fwrite($this->stream, $string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        return fread($this->stream, $length);
    }

    public function getMetadata($key = null): mixed
    {
        $meta = stream_get_meta_data($this->stream);
        return $key ? ($meta[$key] ?? null) : $meta;
    }
}
