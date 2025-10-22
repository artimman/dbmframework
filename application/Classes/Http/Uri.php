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

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $scheme;
    private string $host;
    private ?int $port;
    private string $path;
    private string $query;
    private string $fragment;
    private string $userInfo;

    public function __construct(
        string $path = '/',
        string $scheme = 'http',
        string $host = 'localhost',
        ?int $port = null,
        string $query = '',
        string $fragment = '',
        string $userInfo = ''
    ) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
        $this->userInfo = $userInfo;
    }

    // --- Getters ---

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        return $this->host . ($this->port ? ':' . $this->port : '');
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    // --- with* methods (immutability stubs) ---

    public function withScheme($scheme): static
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function withUserInfo($user, $password = null): static
    {
        $this->userInfo = $user;
        return $this;
    }

    public function withHost($host): static
    {
        $this->host = $host;
        return $this;
    }

    public function withPort($port): static
    {
        $this->port = $port;
        return $this;
    }

    public function withPath($path): static
    {
        $this->path = $path;
        return $this;
    }

    public function withQuery($query): static
    {
        $this->query = $query;
        return $this;
    }

    public function withFragment($fragment): static
    {
        $this->fragment = $fragment;
        return $this;
    }

    public function __toString(): string
    {
        $url = $this->scheme . '://' . $this->host;
        if ($this->port) {
            $url .= ':' . $this->port;
        }

        $url .= $this->path;
        if ($this->query) {
            $url .= '?' . $this->query;
        }

        return $url;
    }
}
