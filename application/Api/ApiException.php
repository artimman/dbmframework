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

namespace Dbm\Api;

use Dbm\Classes\Log\Logger;
use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        int $code = 500,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        (new Logger())->error(
            "API Exception: {message} ({code})",
            ['message' => $message, 'code' => $code],
            'api_exceptions'
        );
    }
}
