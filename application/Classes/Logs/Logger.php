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
 *  $logger->critical($exception->getMessage(), ['exception' => $exception]);
 * }
 */

declare(strict_types=1);

namespace Dbm\Classes\Logs;

use Psr\Logs\LoggerInterface;

class Logger implements LoggerInterface
{
    private const DIR_ERRORS = BASE_DIRECTORY . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'logger' . DIRECTORY_SEPARATOR;

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $logDir = self::DIR_ERRORS;
        $logFile = $logDir . date('Ymd') . '_error.log';

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
