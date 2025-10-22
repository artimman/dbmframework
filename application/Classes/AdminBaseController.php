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

use App\Config\ConstantConfig;
use App\Exception\UnauthorizedRedirectException;
use Dbm\Interfaces\DatabaseInterface;

abstract class AdminBaseController extends BaseController
{
    protected $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        parent::__construct($database);
        $this->database = $database;

        if (empty(getenv('DB_NAME'))) {
            $this->setFlash('messageInfo', 'Brak połączenia z bazą danych.');
            throw new UnauthorizedRedirectException('./start');
        }

        $sessionKey = $this->getSession(getenv('APP_SESSION_KEY'));
        if (empty($sessionKey)) {
            throw new UnauthorizedRedirectException('../login');
        }

        $userId = (int) $sessionKey;
        if ($this->userPermissions($userId) !== ConstantConfig::USER_ROLES['A']) {
            throw new UnauthorizedRedirectException('../');
        }

        /* TODO! Dodatkowo sprawdź ważność sesji (opcjonalne)
        if (!$this->isValidSession($userId)) {
            throw new UnauthorizedRedirectException('../');
        } */
    }

    private function userPermissions(int $userId): ?string
    {
        $query = "SELECT roles FROM dbm_user WHERE id = :id";

        $this->database->queryExecute($query, [':id' => $userId]);
        $result = $this->database->fetchObject() ?: null;

        return $result->roles;
    }

    /* TODO! Można dopisać dodatkowe zabezpieczenie dla penelu administracyjnego
    // Metoda do sprawdzania, czy sesja jest ważna
    protected function isValidSession(int $userId): bool
    {
        // Pobiera sesję użytkownika z bazy
        $query = "SELECT * FROM dbm_user WHERE id = :id";

        $this->database->queryExecute($query, [':id' => $userId]);
        $userSession = $this->database->fetchObject() ?: null;

        // Sprawdzenie, czy sesja istnieje i czy nie wygasła
        if (!$userSession || $userSession->session_expiry < time()) {
            return false; // Sesja wygasła lub nie istnieje
        }

        return true; // Sesja jest ważna
    } */
}
