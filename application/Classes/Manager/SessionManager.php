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

namespace Dbm\Classes\Manager;

class SessionManager
{
    /**
     * Sets a session variable.
     *
     * @param string $sessionName   The name/key of the session variable.
     * @param mixed  $sessionValue  The value to store.
     */
    public function setSession(string $sessionName, mixed $sessionValue): void
    {
        if (!empty($sessionName) && !empty($sessionValue)) {
            $_SESSION[$sessionName] = $sessionValue;
        }
    }

    /**
     * Retrieves a session variable.
     *
     * @param string $sessionName  The name/key of the session variable.
     * @return mixed
     */
    public function getSession(string $sessionName): mixed
    {
        if (!empty($_SESSION[$sessionName])) {
            return $_SESSION[$sessionName];
        }

        return null;
    }

    /**
     * Unsets a session variable.
     *
     * @param string $sessionName The name/key of the session variable.
     */
    public function unsetSession(string $sessionName): void
    {
        if (!empty($sessionName)) {
            unset($_SESSION[$sessionName]);
        }
    }

    /**
     * Destroys all session data.
     */
    public function destroySession(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Retrieves a session variable by reference.
     *
     * Useful for modifying the session value directly.
     *
     * @param string $sessionName  The name/key of the session variable.
     * @return mixed Reference to the session variable.
     */
    public function &getSessionByReference(string $sessionName): mixed
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!array_key_exists($sessionName, $_SESSION)) {
            // Tworzymy klucz, aby referencja była poprawna
            $_SESSION[$sessionName] = null;
        }

        return $_SESSION[$sessionName];
    }
}
