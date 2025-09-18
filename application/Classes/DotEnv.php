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

namespace Dbm\Classes;

use InvalidArgumentException;
use RuntimeException;

class DotEnv
{
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected $path;

    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('ERROR! File %s does not exist or is not readable.', $path));
        }

        $this->path = $path;
    }

    /**
     * Create an immutable instance of DotEnv.
     *
     * @param string $path Path to the .env file
     * @return self
     */
    public static function createImmutable(string $path): self
    {
        return new self($path);
    }

    /**
     * Load environment variables from the .env file.
     *
     * @throws RuntimeException if the file is not readable
     */
    public function load(): void
    {
        if (!is_readable($this->path)) {
            throw new RuntimeException(sprintf('ERROR! %s file is not readable.', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            $value = $this->cleanQuotes($value);
            $value = $this->resolveReferences($value);

            if (!isset($_SERVER[$name]) && !isset($_ENV[$name])) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Clean the quotes from the value if present.
     *
     * @param string $value
     * @return string
     */
    private function cleanQuotes(string $value): string
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Replace variable references like ${VAR} with their respective values.
     *
     * @param string $value
     * @return string
     */
    private function resolveReferences(string $value): string
    {
        return preg_replace_callback('/\${([A-Z0-9_]+)}/', function ($matches) {
            return $_ENV[$matches[1]] ?? $_SERVER[$matches[1]] ?? $matches[0];
        }, $value);
    }
}
