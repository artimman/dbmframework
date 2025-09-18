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

use Exception;
use Throwable;

class ExceptionHandler extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Centralized exception handling method.
     *
     * @param Throwable $exception The exception to handle.
     * @param string $env The application environment ('development' or 'production').
     * @return void
     */
    public function handle(Throwable $exception, string $env = 'production'): void
    {
        $code = $exception->getCode() ?: 500;
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();

        if ($env === 'development') {
            $this->renderDetailedError($code, $message, $file, $line, $exception->getTraceAsString());
        } else {
            $this->renderProductionError($code);
        }
    }

    /**
     * Renders a detailed error page for development environment.
     *
     * @param int $code Error code.
     * @param string $message Error message.
     * @param string $file File where the error occurred.
     * @param int $line Line number where the error occurred.
     * @param string $trace Stack trace of the error.
     * @return void
     */
    private function renderDetailedError(int $code, string $message, string $file, int $line, string $trace): void
    {
        ob_end_clean();

        $formattedMessage = $this->messageReplace($message);

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DbM Framework - Error $code</title>
    <style>
        body { margin: 2rem; font-family: Arial, sans-serif; font-size: 16px; background: #f4f4f4; color: #333; }
        p { margin: 0; padding: 0; }
        .container { max-width: 992px; margin: auto; background: #fff; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; padding: 1rem; background-color: #e11d48; color: #fff; }
        .main { padding: 2rem; }
        .navigation { font-size: 1.5rem; }
        .logo { font-size: 0.9rem; color: #cbd5e1; }
        .title { text-align: right; }
        .title a { font-size: 0.7rem; color: #cbd5e1; text-decoration: none; text-transform: uppercase; }
        .info { word-break: break-word; }
        .info p { padding: 0.3rem; }
        .message { padding: 0.5rem 1rem !important; border: 1px solid #111827; background-color: #334155; color: #fff; border-radius: 5px; }
        .trace { background: #f9f9f9; padding: 1rem; border: 1px solid #ddd; margin-top: 1rem; border-radius: 5px; word-break: break-all; }
        .mb-1 { margin-bottom: 1rem; }
        .wrap { white-space: pre-wrap; }
        .color-ss { color: #cdcd00; font-weight: bold; }
        .color-bracket { color: #00cdcd; }
        .color-quote { color: #00ff00; font-style: italic; }
        .color-file { color: #ff0000; font-weight: bold; }
        .color-number { color: #00ffff; }
        .color-special { color: #ffff00; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="navigation">Error: $code</div>
            <div>
                <div class="logo">DbM Framework Exception</div>
                <div class="title"><a href="https://github.com/designbymalina/dbmframework">Go To Project</a></div>
            </div>
        </div>
        <div class="main">
            <div class="info">
                <p class="message mb-1"><strong>Message:</strong> $formattedMessage</p>
                <p><strong>File:</strong> $file</p>
                <p><strong>Line:</strong> $line</p>
            </div>
            <div class="trace">
                <p class="mb-1"><strong>Trace:</strong></p>
                <p class="wrap">$trace</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

        exit;
    }

    /**
     * Renders a production-friendly error page or redirect.
     *
     * @param int $code Error code.
     * @return void
     */
    private function renderProductionError(int $code): void
    {
        $appPath = getenv('APP_URL') ?: '/';

        switch ($code) {
            case 404:
                header("Location: {$appPath}errors/error-404.html");
                break;
            default:
                header("Location: {$appPath}errors/error.html?code=$code");
                break;
        }

        exit;
    }

    private function messageReplace(string $message): string
    {
        $message = str_replace('SQLSTATE', '<span class="color-ss">SQLSTATE</span>', $message);
        $message = preg_replace('/[\[{\(].*?[\]}\)]/', '<span class="color-bracket">$0</span>', $message);
        $message = preg_replace('/[\'].*?[\']/', '<span class="color-quote">$0</span>', $message);
        $message = preg_replace('/[a-z0-9_\-]*\.php/i', '<span class="color-file">$0</span>', $message);
        $message = preg_replace('/\b\d+\b/', '<span class="color-number">$0</span>', $message);
        $message = preg_replace('/[\(\)#\[\]\':]/i', '<span class="color-special">$0</span>', $message);

        return $message;
    }
}
