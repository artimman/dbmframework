<?php

declare(strict_types=1);

namespace Dbm\Classes\Exceptions;

use Dbm\Classes\Logs\Logger;
use Exception;

class UnauthorizedRedirectException extends Exception
{
    private string $redirectUrl;
    private string $dirTmp;
    private string $logFile;
    private int $limit = 10; // max redirects
    private int $windowSeconds = 600; // 10 min
    private Logger $logger;

    public function __construct(string $redirectUrl, $code = 401, ?Exception $previous = null)
    {
        parent::__construct("Unauthorized! Redirect to: $redirectUrl", $code, $previous);
        $this->redirectUrl = $redirectUrl;

        $this->dirTmp = rtrim(BASE_DIRECTORY, DS) . DS . 'var' . DS . 'tmp' . DS;
        $this->logFile = $this->dirTmp . 'unauth_redirects.log';

        if (!is_dir($this->dirTmp)) {
            mkdir($this->dirTmp, 0775, true);
        }

        $this->logger = new Logger();
        $this->logRedirectAttempt();
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    private function logRedirectAttempt(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $now = time();

        $logs = $this->loadLogs();
        $logs = array_filter($logs, fn ($entry) => $entry['timestamp'] > ($now - $this->windowSeconds));

        $logs[] = ['ip' => $ip, 'timestamp' => $now];
        $this->saveLogs($logs);

        $count = count(array_filter($logs, fn ($entry) => $entry['ip'] === $ip));

        if ($count > $this->limit) {
            $this->logger->alert("Too many unauthorized redirects from IP: $ip");
            $this->addToBlacklist($ip);
        } else {
            $this->logger->alert("Unauthorized redirect for IP $ip to {$this->redirectUrl}. Check it out!");
        }
    }

    private function loadLogs(): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $raw = file_get_contents($this->logFile);
        return $raw ? json_decode($raw, true) : [];
    }

    private function saveLogs(array $logs): void
    {
        file_put_contents($this->logFile, json_encode($logs), LOCK_EX);
    }

    // TODO! Można rozbudować, dodać blokadę itp. (obecnie dla testu tylko zapisuje dane)
    private function addToBlacklist(string $ip): void
    {
        $blacklistFile = $this->dirTmp . 'ip_blacklist.txt';

        if (!file_exists($blacklistFile)) {
            file_put_contents($blacklistFile, "$ip\n", FILE_APPEND | LOCK_EX);
        } else {
            $current = file($blacklistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!in_array($ip, $current)) {
                file_put_contents($blacklistFile, "$ip\n", FILE_APPEND | LOCK_EX);
            }
        }
    }
}
