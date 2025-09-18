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

use Dbm\Classes\ExceptionHandler;

function setupErrorHandling(): void
{
    error_reporting(E_ALL);

    ini_set('display_errors', getenv('APP_ENV') === 'production' ? '0' : '1');

    set_error_handler('reportingErrorHandler');

    set_exception_handler(function (Throwable $exception) {
        (new ExceptionHandler())->handle($exception, getenv('APP_ENV') ?: 'production');
    });
}

function reportingErrorHandler(int $errLevel, string $errMessage, string $errFile, int $errLine): void
{
    logErrorToFile($errLevel, $errMessage, $errFile, $errLine);

    $exceptionHandler = new ExceptionHandler();
    $exception = new ErrorException($errMessage, $errLevel, 0, $errFile, $errLine);
    $exceptionHandler->handle($exception, getenv('APP_ENV') ?: 'production');
}

function logErrorToFile(int $errLevel, string $errMessage, string $errFile, int $errLine): void
{
    $basename = 'index';
    $uri = $_SERVER["REQUEST_URI"];
    $dir = str_replace('public', '', dirname($_SERVER['PHP_SELF']));

    if ($uri !== $dir) {
        $basename = str_replace('.html', '', basename($uri));
        $basename = preg_replace('/[\/\\\\\:\*\?\"\<\>\|\=\&]/', '_', $basename);
        $basename = preg_replace('/\s+/', '_', $basename);
        $basename = preg_replace('/[^\w\.\-]/', '', $basename);
    }

    $date = date('Y-m-d H:i:s');
    $file = date('Ymd') . '_' . strtolower($basename) . '.log';
    $dir = BASE_DIRECTORY . 'var' . DS . 'log' . DS;
    $path = $dir . $file;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $errorHandler = "DATE: $date, level: $errLevel\n File: $errFile on line $errLine\n Message: $errMessage\n";

    file_put_contents($path, $errorHandler, FILE_APPEND);
}

function configurationSettings(string $pathConfig): void
{
    if (!file_exists($pathConfig)) {
        die('CONFIGURATION! Configure the application to run the program, then rename the .env.example file to .env.');
    }
}

function autoloadingWithWithoutComposer(string $pathAutoload): void
{
    if (file_exists($pathAutoload)) {
        require $pathAutoload;
    } else {
        spl_autoload_register(function ($className) {
            $psr4Map = [
                'App\\' => 'src/',
                'Dbm\\' => 'application/',
                'Psr\\' => 'application/Psr/',
                'Lib\\' => 'application/Libraries/',
                'Mod\\' => 'modules/',
            ];

            if (is_dir(BASE_DIRECTORY . 'libraries')) {
                static $loadedLibraries = [];

                $namespaceLibraries = [
                    'PHPMailer\\PHPMailer\\PHPMailer' => "libraries/phpmailer/src",
                ];

                foreach ($namespaceLibraries as $keySpace => $libraryPath) {
                    if (!isset($loadedLibraries[$keySpace]) && !class_exists($keySpace)) {
                        $librarySegments = explode("\\", $keySpace);
                        $fileName = end($librarySegments) . '.php';
                        $libraryPath = str_replace('/', DS, $libraryPath);
                        $filePathLib = BASE_DIRECTORY . $libraryPath . DS . $fileName;

                        if (file_exists($filePathLib)) {
                            require $filePathLib;
                            $loadedLibraries[$keySpace] = true;
                            return;
                        }

                        error_log("Autoloader: File not found for {$keySpace} in path {$filePathLib}");
                    }
                }
            }

            foreach ($psr4Map as $prefix => $baseDir) {
                if (strpos($className, $prefix) === 0) {
                    $relativeClass = substr($className, strlen($prefix));
                    $segments = explode('\\', $relativeClass);

                    if ($prefix === 'Mod\\' && count($segments) > 0) {
                        $moduleName = array_shift($segments);
                        $path = $baseDir . $moduleName . '/' . implode('/', $segments) . '.php';
                    } else {
                        $path = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
                    }

                    $filePath = BASE_DIRECTORY . str_replace('/', DS, $path);

                    if (file_exists($filePath)) {
                        require $filePath;
                        return;
                    } else {
                        error_log("Autoloader: No file found for class {$className} in path {$filePath}");
                        return;
                    }
                }
            }

            error_log("Autoloader: No matching namespace for {$className}");
        });
    }
}

function initializeSession(): void
{
    $isProduction = getenv('APP_ENV') === 'production';

    session_start([
        'cookie_lifetime' => 0,
        'cookie_secure' => $isProduction,
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]);
}

function isConfigDatabase(): bool
{
    return !empty(getenv('DB_HOST')) && !empty(getenv('DB_NAME')) && !empty(getenv('DB_USER'));
}
