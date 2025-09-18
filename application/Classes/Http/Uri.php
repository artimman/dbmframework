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
    public function getScheme(): string
    {
        return '';
    }

    public function getAuthority(): string
    {
        return '';
    }

    public function getUserInfo(): string
    {
        return '';
    }

    public function getHost(): string
    {
        return '';
    }

    public function getPort(): ?int
    {
        return null;
    }

    public function getPath(): string
    {
        return '';
    }

    public function getQuery(): string
    {
        return '';
    }

    public function getFragment(): string
    {
        return '';
    }

    public function withScheme($scheme): static
    {
        return $this;
    }

    public function withUserInfo($user, $password = null): static
    {
        return $this;
    }

    public function withHost($host): static
    {
        return $this;
    }

    public function withPort($port): static
    {
        return $this;
    }

    public function withPath($path): static
    {
        return $this;
    }

    public function withQuery($query): static
    {
        return $this;
    }

    public function withFragment($fragment): static
    {
        return $this;
    }

    public function __toString(): string
    {
        return '';
    }
}
