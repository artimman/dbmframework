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

use App\Enum\RoleEnum;
use Dbm\Exception\UnauthorizedRedirectException;
use Dbm\Interfaces\DatabaseInterface;
use Dbm\Security\AccessControl;

abstract class BaseAdminController extends BaseController
{
    protected ?DatabaseInterface $database;
    protected AccessControl $accessControl;

    public function __construct(?DatabaseInterface $database = null)
    {
        parent::__construct($database);
        $this->database = $database;
        $this->accessControl = new AccessControl($this->database);

        if (empty(getenv('DB_NAME'))) {
            $this->setFlash('messageInfo', '[BaseAdmin] No connection to the database.');
            throw new UnauthorizedRedirectException('./start');
        }

        $sessionKey = $this->getSession(getenv('APP_SESSION_KEY'));
        if (empty($sessionKey)) {
            throw new UnauthorizedRedirectException('../login');
        }

        $userId = (int) $sessionKey;

        if (!$this->accessControl->userHasRole($userId, RoleEnum::ADMIN)) {
            throw new UnauthorizedRedirectException('../');
        }
    }
}
