<?php

declare(strict_types=1);

namespace Dbm\Exception;

use Exception;

class UnauthorizedApiException extends Exception
{
    public function __construct(string $message = "Unauthorized", int $code = 401, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
