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
 * Przykład użycia loggera
 * - - -
 * $logger = new Logger();
 * Logowanie komunikatu
 * $logger->info('Użytkownik zalogował się: {username}', ['username' => 'Jan Kowalski']);
 * Logowanie błędu
 * $logger->error('Nie można połączyć z bazą danych.');
 * Logowanie wyjątku
 * try {
 *  throw new \Exception('Testowy wyjątek');
 * } catch (Exception $exception) {
 *  $context = ['query' => $query];
 *  $logger->critical($exception->getMessage() . " | Query: {query}", $context);
 * }
 */

declare(strict_types=1);

namespace Dbm\Classes\Log;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

class Logger implements LoggerInterface
{
    private const DIR_ERRORS = BASE_DIRECTORY . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'logger' . DIRECTORY_SEPARATOR;

    private const VALID_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws InvalidArgumentException
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        // Validate log level according to PSR-3
        if (!is_string($level) || !in_array($level, self::VALID_LEVELS, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid log level: %s. Must be one of: %s', $level, implode(', ', self::VALID_LEVELS))
            );
        }

        $logDir = self::DIR_ERRORS;
        $logFile = $logDir . date('Ymd') . "_{$level}.log";

        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                @error_log("Logger: Failed to create log directory: $logDir");
                return;
            }
        }

        // Convert Stringable to string
        $messageString = $message instanceof Stringable ? (string) $message : $message;

        // Interpolate message with context
        $interpolatedMessage = $this->interpolateMessage($messageString, $context);

        // Format log entry
        $logEntry = sprintf("[%s] %s: %s" . PHP_EOL, date('Y-m-d H:i:s'), strtoupper($level), $interpolatedMessage);

        // Check if directory is writable
        if (!is_writable($logDir)) {
            @error_log("Logger: Log directory is not writable: $logDir");
            return;
        }

        // Write to log file
        $result = @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            @error_log("Logger: Failed to write to log file: $logFile");
        }
    }

    /**
     * Interpolates placeholders in the message with context values.
     *
     * @param string $message Message with placeholders {key}
     * @param array $context Context array with values to interpolate
     * @return string Message with interpolated values
     */
    private function interpolateMessage(string $message, array $context): string
    {
        foreach ($context as $key => $value) {
            // Special handling for exceptions according to PSR-3
            if ($key === 'exception' && $value instanceof \Throwable) {
                $value = sprintf(
                    "%s: %s in %s:%d\nStack trace:\n%s",
                    get_class($value),
                    $value->getMessage(),
                    $value->getFile(),
                    $value->getLine(),
                    $value->getTraceAsString()
                );
            }

            $message = str_replace("{{$key}}", (string) $value, $message);
        }
        return $message;
    }
}
