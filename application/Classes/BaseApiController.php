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

use Dbm\Classes\DependencyContainer;
use Dbm\Classes\Translation;
use Dbm\Classes\Helpers\TranslationLoader;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Http\Response;
use Dbm\Classes\Http\Stream;
use Dbm\Classes\Manager\SessionManager;
use Dbm\Exception\UnauthorizedApiException;
use Dbm\Interfaces\BaseApiInterface;
use Dbm\Interfaces\DatabaseInterface;
use Psr\Http\Message\ResponseInterface;

abstract class BaseApiController implements BaseApiInterface
{
    private ?DatabaseInterface $database = null;
    protected ?Translation $translation = null;
    protected ?Request $request = null;
    protected static ?DependencyContainer $diContainer = null;
    protected SessionManager $session;

    public function __construct(?DatabaseInterface $database = null, ?Request $request = null)
    {
        $this->database = $database;
        $this->request = $request ?? new Request();
        $this->translation = (new TranslationLoader())->load();
        $this->session = $session ?? new SessionManager();

        if (self::$diContainer === null) {
            self::$diContainer = new DependencyContainer();
        }
    }

    /**
     * Zwraca sesję
     */
    public function getSession(string $sessionName): mixed
    {
        return $this->session->getSession($sessionName);
    }

    /**
     * Zwraca instancję bazy
     */
    public function getDatabase(): ?DatabaseInterface
    {
        return $this->database;
    }

    /**
     * Zwraca DI Container
     */
    public function getDIContainer(): DependencyContainer
    {
        return self::$diContainer;
    }

    /**
     * Szybka odpowiedź JSON
     */
    public function jsonResponse(
        array|string|int|float|bool|null $data,
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);

        if (!is_string($data)) {
            $data = json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        $stream = new Stream($data ?? '');

        return new Response(
            $status,
            $headers,
            $stream
        );
    }

    /**
     * Odpowiedź sukcesu w JSON
     */
    public function successResponse(array $data = [], int $status = 200): ResponseInterface
    {
        return $this->jsonResponse([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * Odpowiedź błędu w JSON
     */
    public function errorResponse(string $message, int $status = 500): ResponseInterface
    {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Helper
     */
    public static function error(string $message, int $status = 500): ResponseInterface
    {
        $headers = ['Content-Type' => 'application/json'];
        $stream  = new Stream(json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return new Response($status, $headers, $stream);
    }

    protected function authorizeApiAccess(?string $role = null): void
    {
        $sessionKey = $this->getSession(getenv('APP_SESSION_KEY'));

        if (empty($sessionKey)) {
            throw new UnauthorizedApiException("Unauthorized API access!");
        }

        if (!empty($role)) {
            $userId = (int) $sessionKey;
            $userRole = $this->userPermissions($userId);

            if ($userRole !== $role) {
                throw new UnauthorizedApiException("Unauthorized API access!");
            }
        }
    }

    private function userPermissions(int $userId): ?string
    {
        $query = "SELECT roles FROM dbm_user WHERE id = :id";

        $this->database->queryExecute($query, ['id' => $userId]);
        $result = $this->database->fetchObject() ?: null;

        return $result->roles;
    }
}
