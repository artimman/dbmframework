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
 * PSR-7: StreamInterface, Code modified by: Design by Malina
 */

declare(strict_types=1);

namespace Psr\Http\Message;

/**
 * Describes a data stream.
 *
 * Typically used to encapsulate a file stream, network stream, or in-memory
 * stream, and provides a common interface for interacting with these streams.
 */
interface StreamInterface
{
    /**
     * Creates a new stream instance from the given string content.
     *
     * This method provides a convenient way to create a stream without directly instantiating the class.
     *
     * @param string $content The content to be written into the stream.
     * @return self A new Stream instance containing the provided content.
     */
    public static function create(string $content): self;

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Close the stream and any underlying resources.
     *
     * @return void
     */
    public function close(): void;

    /**
     * Detach the underlying resource from the stream.
     *
     * After the stream has been detached, all operations will fail. If a resource
     * is provided, this method should return it.
     *
     * @return resource|null
     */
    public function detach();

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize(): ?int;

    /**
     * Returns the current position of the file read/write pointer.
     *
     * @return int
     * @throws \RuntimeException on error.
     */
    public function tell(): int;

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool;

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool;

    /**
     * Seek to a position in the stream.
     *
     * @param int $offset Stream offset.
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical to
     *                    the built-in PHP `fseek()` function.
     * @return void
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET): void;

    /**
     * Rewind the stream to the beginning.
     *
     * @return void
     * @throws \RuntimeException on failure.
     */
    public function rewind(): void;

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool;

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string): int;

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool;

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if underlying
     *                    stream call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *                if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length): string;

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *                           reading.
     */
    public function getContents(): string;

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @param string|null $key Specific metadata to retrieve.
     * @return mixed|null Returns an associative array if no key is provided. Returns a specific
     *                    key value if a key is provided and the value is found, or null if the
     *                    key is not found.
     */
    public function getMetadata($key = null): mixed;
}
