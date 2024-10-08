<?php
/*
 * DbM Framework
 * All code copyright Design by Malina
 * DbM: www.dbm.org.pl
 */

declare(strict_types=1);

namespace App\Utility;

use Exception;

class ErrorLoggerUtility
{
    private const DIR_ERRORS = BASE_DIRECTORY . 'var' . DS . 'log' . DS . 'logger' . DS;

    public function log(string $message, string $level = 'error'): void
    {
        if (!is_dir(self::DIR_ERRORS)) {
            !mkdir(self::DIR_ERRORS, 0777, true);
        }

        $logEntry = "DATE: " . date('Y-m-d H:i:s') . ", level: $level" . "\n";

        if ($level === 'exception') {
            $logEntry .= $message;
        } else {
            $logEntry .= " Message: " . $message . "\n";
        }

        $filePath = self::DIR_ERRORS . date('Ymd') . '_error.log';

        file_put_contents($filePath, $logEntry, FILE_APPEND);
    }

    public function logException(Exception $exception): void
    {
        $logMessage = " Exception: " . $exception->getMessage() . " in " . $exception->getFile()
            . " on line " . $exception->getLine() . "\nStack trace:\n" . $exception->getTraceAsString() . "\n";

        $this->log($logMessage, 'exception');
    }
}

/* Example of use:
class Example
{
    private $logger;

    public function __construct(ErrorLogger $logger)
    {
        $this->logger = $logger;
    }

    public function firstMethod()
    {
        try {
            // $this->execute(...);
        } catch (\Exception $e) {
            $this->logger->logException($e);
        }
    }

    public function someMethod()
    {
        $id = (int) $this->requestData('id');

        if ($id > 0) {
            $this->changeProductStatus($id, $status);
        } else {
            $this->logger->log("Invalid ID = $id", 'WARNING');
        }
    }
}
*/
