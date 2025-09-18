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

namespace Dbm\Classes\Managers;

class CookieManager
{
    /**
     * Sets a cookie.
     *
     * @param string $cookieName   The name/key of the cookie.
     * @param string $cookieValue  The value to store in the cookie.
     * @param int    $expiry       Expiry time in seconds (default: 86400 = 1 day).
     * @param bool   $secure       Indicates if the cookie should be sent only over HTTPS.
     * @param bool   $httpOnly     Indicates if the cookie is accessible only through HTTP protocol.
     */
    public function setCookie(string $cookieName, string $cookieValue, int $expiry = 86400, bool $secure = true, bool $httpOnly = true): void
    {
        if (!empty($cookieName) && !empty($cookieValue)) {
            setcookie($cookieName, $cookieValue, time() + $expiry, '/', '', $secure, $httpOnly);
            $_COOKIE[$cookieName] = $cookieValue;
        }
    }

    /**
     * Retrieves a cookie value.
     *
     * @param string $cookieName   The name/key of the cookie.
     * @return string|null         The cookie value, or null if not set.
     */
    public function getCookie(string $cookieName): ?string
    {
        if (isset($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }

        return null;
    }

    /**
     * Deletes a cookie.
     *
     * @param string $cookieName  The name/key of the cookie to delete.
     */
    public function unsetCookie(string $cookieName): void
    {
        if (isset($_COOKIE[$cookieName])) {
            setcookie($cookieName, '', time() - 3600, '/', '', true, true);
            unset($_COOKIE[$cookieName]);
        }
    }
}
