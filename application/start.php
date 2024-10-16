<?php
/*
 * Application: DbM Framework version 2
 * Author: Arthur Malinowski (C) Design by Malina
 * Web page: www.dbm.org.pl
 * License: MIT
*/

function setupErrorHandling(): void
{
    error_reporting(E_ALL);
    ini_set('display_errors', getenv('APP_ENV') === 'production' ? '0' : '1');
    set_error_handler('reportingErrorHandler');
}

function reportingErrorHandler(int $errLevel, string $errMessage, string $errFile, int $errLine): void
{
    $basename = 'index';
    $uri = $_SERVER["REQUEST_URI"];
    $dir = str_replace('public', '', dirname($_SERVER['PHP_SELF']));

    if ($uri !== $dir) {
        $basename = str_replace('.html', '', basename($_SERVER["REQUEST_URI"]));

        if (strpos($uri, '.') !== false) {
            preg_match('/\.(.*?)\./', $uri, $match);

            if (array_key_exists(1, $match)) {
                $basename = $match[1];
            }
        }

        $basename = preg_replace('/[\/\\\\\:\*\?\"\<\>\|\=\&]/', '_', $basename);
        $basename = preg_replace('/\s+/', '_', $basename);
        $basename = preg_replace('/[^\w\.\-]/', '', $basename);
    }

    $date = date('Y-m-d H:i:s');
    $file = date('Ymd') . '_' . $basename . '.log';
    $dir =  BASE_DIRECTORY . 'var' . DS . 'log' . DS;
    $path = $dir . $file;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $errorHandler = "DATE: $date, level: $errLevel\n File: $errFile on line $errLine\n Message: $errMessage\n";

    $handle = fopen($path, 'a');
    fwrite($handle, $errorHandler);
    fclose($handle);

    if (!empty($errLine)) {
        htmlErrorHandler($errMessage, $errFile, $errLine);
    }
}

function htmlErrorHandler(string $message, string $file, int $line): void
{
    ob_end_clean();

    echo('<!DOCTYPE html>' . "\n"
        . '<html lang="en">' . "\n"
        . '<head>' . "\n"
        . '  <meta charset="utf-8">' . "\n"
        . '  <meta name="author" content="Design by Malina, www.dbm.org.pl">' . "\n"
        . '  <title>DbM Framework - Error Handler</title>' . "\n"
        . '  <style>' . "\n"
        . '    body { margin: 0; background-color: #181818; color: white; } h2 { margin: 0; color: #a97bd3; } u { color: yellow; text-decoration: none; } b { color: red; } em { color: blue; font-style: normal; } ss { color: orange; } .dbm-container { margin: 0 auto; max-width: 1000px; } .dbm-header { margin-bottom: 3rem; padding: 5px 10px; background-color: #181818; color: grey; } .dbm-content { padding: 2rem; background-color: rgba(255,255,255,0.1); border-radius: 0.5rem; font-size: 1.0rem; } .dbm-content ul { list-style-type: none; } .dbm-content ul li { padding: 5px 10px; } .dbm-content ul li.msg { background-color: #b1413f; } .dbm-content ul li.file {  background-color: #666; }' . "\n"
        . '  </style>' . "\n"
        . '</head>' . "\n"
        . '<body>' . "\n"
        . '  <div class="dbm-container">' . "\n"
        . '    <div class="dbm-header">DbM Fremwork Handler Reporting</div>' . "\n"
        . '    <div class="dbm-content">' . "\n"
        . '      <h2>Oops, something went wrong!</h2>' . "\n"
        . '      <ul><li class="msg">Message: ' . nl2br($message) . '</li><li class="file">File: ' . basename(dirname($file)) . DS . basename($file)  . ' on line ' . $line . '</li></ul>' . "\n"
        . '    </div>' . "\n"
        . '  </div>' . "\n"
        . '</body>' . "\n"
        . '</html>');

    exit();
}

function configurationSettings(string $pathConfig): void
{
    if (!file_exists($pathConfig)) {
        die('CONFIGURATION! Configure the application to run the program, then rename the .env.dist file to .env.');
    }
}

function autoloadingWithWithoutComposer(string $pathAutoload): void
{
    if (file_exists($pathAutoload)) {
        require($pathAutoload);
    } else {
        spl_autoload_register(function ($className) {
            $arrayClassName = explode("\\", $className);
            $firstLocation = reset($arrayClassName);

            $arrayClass = array_map(function ($value, $key) {
                return $key == 1 ? strtolower($value) : $value;
            }, $arrayClassName, array_keys($arrayClassName));

            $className = implode(DS, $arrayClassName);
            $classNameLower = implode(DS, $arrayClass);

            if ($firstLocation === 'App') {
                $className = str_replace('App', 'src', $className);
            } else {
                $className = str_replace('Dbm', 'application', $classNameLower);
            }

            require(BASE_DIRECTORY . $className . '.php');
        });
    }
}

function initializeSession(): void
{
    $isProduction = getenv('APP_ENV') === 'production';

    session_start([
        'cookie_lifetime' => 0,              // Cookie lifespan (it has an influence on class RememberMe)
        'cookie_secure' => $isProduction,    // Cookie only available via HTTPS
        'cookie_httponly' => true,           // Cookie not available via JavaScript
        'use_strict_mode' => true,           // Enforcing Strict Session Mode
        'use_only_cookies' => true,          // Only cookies for storing sessions
    ]);
}
