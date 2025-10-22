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

namespace Dbm\Classes\Services;

use Dbm\Interfaces\DatabaseInterface;
use Dbm\Classes\RememberMe;
use Dbm\Classes\BaseController;

class RememberMeService
{
    private ?RememberMe $rememberMe = null;

    public function __construct(?DatabaseInterface $database, BaseController $controller)
    {
        if (!empty(getenv('DB_NAME')) && $database) {
            $stmt = $database->querySql("SHOW TABLES LIKE 'dbm_remember_me'");

            if ($stmt->rowCount() > 0) {
                $this->rememberMe = new RememberMe($database, $controller);
                $this->rememberMe->checkRememberMe($controller);
            }
        }
    }

    public function getRememberMe(): ?RememberMe
    {
        return $this->rememberMe;
    }
}
