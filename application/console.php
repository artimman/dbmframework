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
 * DOCUMENTATION: Examples can be found in the README documentation -> Console Commands
 */

declare(strict_types=1);

// TODO! Maybe remove the definition from the entire project?
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIRECTORY', str_replace('application' . DS, '', __DIR__ . DS));

require(BASE_DIRECTORY . 'application' . DS . 'start.php');

$pathConfig = BASE_DIRECTORY . '.env';
$pathAutoload = BASE_DIRECTORY . 'vendor' . DS . 'autoload.php';

configurationSettings($pathConfig);
autoloadingWithWithoutComposer($pathAutoload);

### START
use Dbm\Classes\DotEnv;

// Environment Variables
$dotEnv = new DotEnv($pathConfig);
$dotEnv->load();

### CONSOLE COMMANDS
// Example command from folder: application> php console.php Example (by CommandInterface)
if (!empty($argv) && (count($argv) > 1)) {
    $argvClass = $argv[1];
    $fileClass = BASE_DIRECTORY . 'src' . DS . 'Command' . DS . $argvClass. 'Command.php';
    $class = 'App\\Command\\' . $argvClass . 'Command';

    if (file_exists($fileClass)) {
        require_once($fileClass);

        $method = new $class();
        $method->execute();
    } else {
        echo "\033[43mNot found class '$argvClass'\033[0m \n";
    }
} else {
    echo "\033[43mINFO! Provide the call parameters. Example: php console.php Example\033[0m \n";
}
