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
 * DOCUMENTATION: Examples can be found in the README documentation -> Console
 *
 * Usage (CLI): php application/worker.php
 * Cron systemowy: * / 5 * * * * php /var/www/project/application/worker.php
 */

declare(strict_types=1);

/* // TEMP: Example worker, namespace App\Console\Worker;
class ExampleWorker
{
    public function run(): void
    {
        echo "Running ExampleWorker..." . PHP_EOL;
        sleep(1); // symulacja pracy
        echo "ExampleWorker finished successfully." . PHP_EOL;
    }
} */

use Dbm\Classes\DotEnv;
use Dbm\Classes\DatabaseSingleton;

// --- Define paths ---
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIRECTORY', str_replace('application' . DS, '', __DIR__ . DS));

// --- Bootstrap ---
require BASE_DIRECTORY . 'application' . DS . 'start.php';

$pathConfig   = BASE_DIRECTORY . '.env';
$pathAutoload = BASE_DIRECTORY . 'vendor' . DS . 'autoload.php';

// --- Configuration ---
configurationSettings($pathConfig);
autoloadingWithWithoutComposer($pathAutoload);

// --- Load environment variables ---
$dotenv = new DotEnv($pathConfig);
$dotenv->load();

// --- Start timer ---
$startTime = microtime(true);

// --- Initialize database ---
$database = DatabaseSingleton::getInstance();

// --- Start worker ---
echo "==========" . PHP_EOL;
echo "Worker started at " . date('Y-m-d H:i:s') . PHP_EOL;
echo "----------" . PHP_EOL;

try {
    // Uruchomienie przykładowego workera
    // $worker = new ExampleWorker($database);
    // $worker->run();

    $status = "\033[32mWorker completed successfully\033[0m";
} catch (Throwable $exception) {
    $status = "\033[31mWorker error: " . $exception->getMessage() . "\033[0m";
    echo $status . PHP_EOL;
} finally {
    // --- Cleanup ---
    $database->close();

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 3);
    $memory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

    echo "----------" . PHP_EOL;
    echo "Worker finished at " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "    Duration: {$duration}s | Memory peak: {$memory} MB" . PHP_EOL;
    echo "    Status: $status" . PHP_EOL;
    echo "==========" . PHP_EOL;
}
