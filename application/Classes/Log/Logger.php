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

use Psr\Log\LoggerInterface;
use Stringable;

class Logger implements LoggerInterface
{
    private const DIR_ERRORS = BASE_DIRECTORY . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'logger' . DIRECTORY_SEPARATOR;

    public function emergency(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('emergency', $message, $context, $channel);
    }

    public function alert(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('alert', $message, $context, $channel);
    }

    public function critical(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('critical', $message, $context, $channel);
    }

    public function error(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('error', $message, $context, $channel);
    }

    public function warning(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('warning', $message, $context, $channel);
    }

    public function notice(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('notice', $message, $context, $channel);
    }

    public function info(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('info', $message, $context, $channel);
    }

    public function debug(string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $this->log('debug', $message, $context, $channel);
    }

    public function log($level, string|Stringable $message, array $context = [], ?string $channel = null): void
    {
        $channel = $channel ?? 'error';
        $logDir = self::DIR_ERRORS;
        $logFile = $logDir . date('Ymd') . "_{$channel}.log";

        if (!is_dir($logDir) && !mkdir($logDir, 0777, true) && !is_dir($logDir)) {
            error_log("Nie udało się utworzyć katalogu logów: $logDir");
            return;
        }

        $interpolatedMessage = $this->interpolateMessage($message, $context);

        $logEntry = sprintf("[%s] %s: %s" . PHP_EOL, date('Y-m-d H:i:s'), strtoupper($level), $interpolatedMessage);

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    private function interpolateMessage(string $message, array $context): string
    {
        foreach ($context as $key => $value) {
            $message = str_replace("{{$key}}", (string) $value, $message);
        }
        return $message;
    }
}
