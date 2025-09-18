<?php

declare(strict_types=1);

namespace Dbm\Classes\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function __construct($message = "Not Found", $code = 404, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
