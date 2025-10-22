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

namespace Dbm\Interfaces;

use Dbm\Classes\DependencyContainer;
use Dbm\Classes\Translation;
use Psr\Http\Message\ResponseInterface;

interface BaseInterface
{
    public function getDatabase(): ?DatabaseInterface;

    public function getTranslation(): ?Translation;

    public function getDIContainer(): ?DependencyContainer;

    public function setSession(string $sessionName, mixed $sessionValue): void;

    public function getSession(string $sessionName): mixed;

    public function unsetSession(string $sessionName): void;

    public function destroySession(): void;

    public function &getSessionByReference(string $sessionName): mixed;

    public function setCookie(string $cookieName, string $cookieValue, int $expiry = 86400, bool $secure = true, bool $httpOnly = true): void;

    public function getCookie(string $cookieName): ?string;

    public function unsetCookie(string $cookieName): void;

    public function setFlash(string $sessionName, string $message): void;

    public function flash(?string $sessionName = null): ?array;

    public function redirect(string $path, array $params = []): ResponseInterface;

    public function getCsrfToken(): string;
}
