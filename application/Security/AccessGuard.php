<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * Examples of usage in routes.php
 * // Account routes (permission: access_to_account)
 * $router->guard('access_to_account', function () use ($router) {
 *     $router->get('/account', [AccountController::class, 'index'], 'account');
 *     $router->get('/account/profile', [AccountController::class, 'accountProfile'], 'account_profile');
 * });
 * // Admin routes (permission: access_to_admin_panel with prefix '/admin')
 * $router->guard('access_to_admin_panel', function() use ($router) {
 *     $router->get('/dashboard', [PanelController::class, 'index'], 'admin_dashboard');
 *     $router->get('/settings', [PanelController::class, 'settings'], 'admin_settings');
 * }, '/admin');
 */

declare(strict_types=1);

namespace Dbm\Security;

use Dbm\Classes\ExceptionHandler;
use Dbm\Classes\Manager\SessionManager;
use Dbm\Interfaces\DatabaseInterface;
use Dbm\Security\AccessControl;
use Exception;
use Throwable;

class AccessGuard
{
    private AccessControl $access;
    private SessionManager $session;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->access = new AccessControl($database);
        $this->session = new SessionManager();
    }

    /**
     * Sprawdza, czy aktualny użytkownik ma określone uprawnienie.
     *
     * @param string $permission Nazwa uprawnienia do sprawdzenia
     * @return void
     */
    public function checkPermission(string $permission): void
    {
        try {
            $sessionKey = $this->session->getSession(getenv('APP_SESSION_KEY')) ?? null;

            if (!$sessionKey) {
                throw new Exception("Unauthorized access - session not found", 401);
            }

            $userId = (int) $sessionKey;

            if (!$this->access->userCan($userId, $permission)) {
                throw new Exception("Forbidden - insufficient permissions for '{$permission}'", 403);
            }
        } catch (Throwable $exeption) {
            (new ExceptionHandler())->handle($exeption, getenv('APP_ENV') ?: 'production');
            exit;
        }
    }
}
