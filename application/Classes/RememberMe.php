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

namespace Dbm\Classes;

use Dbm\Interfaces\DatabaseInterface;

class RememberMe
{
    public const COOKIE_REMEMBER_ME = 'dbmRememberMe';

    private DatabaseInterface $database;
    private BaseController $controller;

    public function __construct(DatabaseInterface $database, BaseController $controller)
    {
        $this->database = $database;
        $this->controller = $controller;
    }

    public function checkRememberMe(): bool
    {
        if ($this->controller->getSession(getenv('APP_SESSION_KEY'))) {
            return true;
        }

        $rememberMeToken = $this->controller->getCookie(self::COOKIE_REMEMBER_ME);

        if ($rememberMeToken && preg_match('/^[a-f0-9]{16}:[a-f0-9]{64}$/', $rememberMeToken)) {
            $userRemember = $this->findUserByToken($rememberMeToken);

            if ($userRemember) {
                session_regenerate_id(true);
                $this->controller->setSession(getenv('APP_SESSION_KEY'), (string) $userRemember->user_id);
                $this->createRememberMe($userRemember->user_id);
                return true;
            }
            $this->controller->unsetCookie(self::COOKIE_REMEMBER_ME);
        }

        return false;
    }

    public function createRememberMe(int $userId): bool
    {
        $selector = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

        $tokenData = [
            'user_id'   => $userId,
            'selector'  => $selector,
            'validator' => $hashedValidator,
            'expiry'    => $expiry,
        ];

        if ($this->insertUserToken($tokenData)) {
            $this->controller->setCookie(self::COOKIE_REMEMBER_ME, "$selector:$hashedValidator", 86400 * 30);
            return true;
        }

        return false;
    }

    public function removeRememberMe(): void
    {
        $sessionUserId = $this->controller->getSession(getenv('APP_SESSION_KEY'));

        if ($sessionUserId !== null) {
            $this->deleteUserToken((int) $sessionUserId);
        }

        $this->controller->unsetSession(getenv('APP_SESSION_KEY'));
        $this->controller->unsetCookie(self::COOKIE_REMEMBER_ME);
    }

    private function findUserByToken(string $token): ?object
    {
        $tokens = explode(':', $token);
        if (count($tokens) !== 2) {
            return null;
        }

        $query = "SELECT * FROM dbm_remember_me WHERE selector=:selector AND expiry > now() LIMIT 1";
        $this->database->queryExecute($query, [':selector' => $tokens[0]]);

        return $this->database->rowCount() ? $this->database->fetchObject() : null;
    }

    private function insertUserToken(array $params): bool
    {
        [$filteredQuery, $filteredData] = $this->database->buildInsertQuery($params, 'dbm_remember_me');
        return $this->database->queryExecute($filteredQuery, $filteredData);
    }

    private function deleteUserToken(int $uid): bool
    {
        return $this->database->queryExecute("DELETE FROM dbm_remember_me WHERE user_id=:user_id", [':user_id' => $uid]);
    }
}
