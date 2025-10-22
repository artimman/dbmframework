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

namespace Dbm\Classes\Services;

use Dbm\Classes\Helpers\TranslationLoader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class InstallerService
{
    private const DIR_INSTALL = '_Documents';
    private const PATH_ROUTES = 'application' . DS . 'routes.php';
    private const PATH_CONSTANT = 'src' . DS . 'Config' . DS . 'ConstantConfig.php';
    private const PATH_MODULE_ROUTES = '_Modifications' . DS . 'routes.php';
    private const PATH_MODULE_CONSTANT = '_Modifications' . DS . 'constants.php';
    private const PATH_BACKUP_BASE = self::DIR_INSTALL . DS . 'BackupBaseFiles';

    public function installModule(string $dirModule, string $pathManifest, string $pathZip): array
    {
        $result = $this->unpackModule($dirModule, $pathZip);
        return $result['type'] === 'success' ? $this->prepareModuleInstallation($dirModule, $pathManifest) : $result;
    }

    public function uninstallModule(string $dirModule, string $pathManifest): array
    {
        if (!file_exists($pathManifest)) {
            return ['type' => 'danger', 'message' => 'Install manifest not found.'];
        }

        $manifest = json_decode(file_get_contents($pathManifest), true);
        if (!isset($manifest['target'], $manifest['files'])) {
            return ['type' => 'danger', 'message' => 'Invalid manifest structure.'];
        }

        $this->removeRoutes($dirModule);
        $this->removeConstants($dirModule);
        $this->updateTranslations('APP_LANGUAGES', '');
        $this->deleteModuleFiles($manifest['target'], $manifest['files']);
        $this->restoreBaseFiles($manifest['files'], $manifest['target']);

        $this->waitForModuleState($pathManifest, false);

        return ['type' => 'success', 'message' => 'The installer and configuration files have been completely removed.'];
    }

    private function prepareModuleInstallation(string $dirModule, string $pathManifest): array
    {
        if (!file_exists($pathManifest)) {
            return ['type' => "danger", 'message' => "Install manifest not found: <code>$pathManifest</code>"];
        }

        $manifest = json_decode(file_get_contents($pathManifest), true);
        if (!$manifest || !isset($manifest['files'], $manifest['target'])) {
            return ['type' => "danger", 'message' => "Invalid install manifest structure."];
        }

        $this->backupBaseFiles($manifest['files'], $manifest['target']);
        $this->copyModuleFiles($manifest['files'], $manifest['target']);
        $this->updateTranslations('APP_LANGUAGES', 'EN|PL');
        $this->modifyConstants($dirModule);
        $this->modifyRoutes(BASE_DIRECTORY . self::PATH_ROUTES, $dirModule . DS . self::PATH_MODULE_ROUTES);

        $this->waitForModuleState($pathManifest, true);

        return ['type' => "success", 'message' => 'Installation completed successfully.'];
    }

    /** INFO! Można rozszerzyć. Problem z czasem sprawdzania kopiowania/usuwania plików na różnych dyskach i systemach. */
    private function waitForModuleState(string $manifestPath, bool $shouldExist): void
    {
        if (!file_exists($manifestPath)) {
            return;
        }

        sleep($shouldExist ? 5 : 3);
    }

    private function unpackModule(string $dirModule, string $pathZip): array
    {
        if (file_exists($dirModule)) {
            return ['type' => 'success', 'message' => 'Install module already extracted.'];
        }

        if (!file_exists($pathZip)) {
            return ['type' => 'info', 'message' => 'Installation archive not found in ' . self::DIR_INSTALL . ' directory. Download installer from project home page.'];
        }

        $zip = new ZipArchive();

        if ($zip->open($pathZip) === true) {
            $zip->extractTo(BASE_DIRECTORY . self::DIR_INSTALL);
            $zip->close();
            return ['type' => 'success', 'message' => 'Install module extracted successfully.'];
        }

        return ['type' => 'danger', 'message' => 'Unable to open install.zip archive.'];
    }

    private function copyModuleFiles(array $arrayFiles, array $arrayTargets): void
    {
        foreach ($arrayFiles as $key => $src) {
            if (!isset($arrayTargets[$key])) {
                continue;
            }

            $source = BASE_DIRECTORY . $src;
            $target = BASE_DIRECTORY . $arrayTargets[$key];

            if (is_dir($source)) {
                $this->copyRecursive($source, $target);
            } elseif (is_file($source)) {
                $this->safeCopy($source, $target);
            }
        }
    }

    private function deleteModuleFiles(array $arrayTargets, array $arrayFiles): void
    {
        foreach ($arrayTargets as $key => $targetRel) {
            $sourceRel = $arrayFiles[$key] ?? null;
            $target = BASE_DIRECTORY . $targetRel;
            $source = $sourceRel ? BASE_DIRECTORY . $sourceRel : null;

            if (is_file($target)) {
                unlink($target);
                $this->deleteDirIfEmpty(dirname($target));
            } elseif (is_dir($target) && $source && is_dir($source)) {
                $this->removeMatchingFiles($source, $target);
                $this->deleteDirIfEmpty($target);
            }
        }
    }

    private function removeMatchingFiles(string $sourceDir, string $targetDir): void
    {
        foreach (scandir($sourceDir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $source = $sourceDir . DS . $item;
            $target = $targetDir . DS . $item;

            if (is_dir($source) && is_dir($target)) {
                $this->removeMatchingFiles($source, $target);
                $this->deleteDirIfEmpty($target);
            } elseif (is_file($source) && file_exists($target)) {
                unlink($target);
            }
        }
    }

    private function deleteDirIfEmpty(string $dir): void
    {
        if (is_dir($dir) && count(glob($dir . '/*')) === 0) {
            rmdir($dir);
        }
    }

    private function modifyConstants(string $dirModule): void
    {
        $constantsFile = $dirModule . DS . self::PATH_MODULE_CONSTANT;
        $targetPath = BASE_DIRECTORY . self::PATH_CONSTANT;

        if (!file_exists($constantsFile) || !file_exists($targetPath)) {
            return;
        }

        include $constantsFile;
        $content = file_get_contents($targetPath);

        if (isset($installConstants)) {
            if (preg_match('/\/\/-KEY_CONSTANTS_(\w+)/', $installConstants, $matches)) {
                $key = $matches[0];

                if (!str_contains($content, $key)) {
                    $content = str_replace(
                        "//-INSTALL_POINT_ADD_CONSTANT",
                        trim($installConstants) . "\n\n    //-INSTALL_POINT_ADD_CONSTANT",
                        $content
                    );
                    file_put_contents($targetPath, $content);
                }
            }
        }
    }

    private function removeConstants(string $dirModule): void
    {
        $constantsFile = $dirModule . DS . self::PATH_MODULE_CONSTANT;
        $targetPath = BASE_DIRECTORY . self::PATH_CONSTANT;

        if (!file_exists($constantsFile) || !file_exists($targetPath)) {
            return;
        }

        include $constantsFile;
        $content = file_get_contents($targetPath);

        if (isset($installConstants)) {
            if (preg_match_all('/\/\/-KEY_CONSTANTS_(\w+)/', $installConstants, $matches)) {
                foreach ($matches[0] as $keyMarker) {
                    $pattern = '/[ \t]*' . preg_quote($keyMarker, '/') . '.*?\n{2,}[ \t]*(?=    \/\/-INSTALL_POINT_ADD_CONSTANT)/s';
                    $content = preg_replace($pattern, '', $content);
                }
                file_put_contents($targetPath, $content);
            }
        }
    }

    private function modifyRoutes(string $routesPath, string $routesFile): void
    {
        if (!file_exists($routesPath) || !file_exists($routesFile)) {
            return;
        }

        include $routesFile;
        $content = file_get_contents($routesPath);

        if (!isset($installUses, $installRoutes, $installValues)) {
            return;
        }

        $newUseLines = [];
        foreach (explode("\n", trim($installUses)) as $line) {
            $line = trim($line);
            if ($line !== '' && !str_contains($content, $line)) {
                $newUseLines[] = $line;
            }
        }

        $newRouteLines = [];
        foreach (explode("\n", trim($installRoutes)) as $line) {
            $line = trim($line);
            if ($line !== '' && !str_contains($content, $line)) {
                $newRouteLines[] = $line;
            }
        }

        $newValueLines = [];
        foreach (explode("\n", trim($installValues)) as $line) {
            $line = trim($line);
            if ($line !== '' && !str_contains($content, $line)) {
                $newValueLines[] = $line;
            }
        }

        $modified = false;

        if (!empty($newUseLines)) {
            $content = preg_replace(
                '/(\/\/\-INSTALL_POINT_ADD_USE)/',
                implode("\n", $newUseLines) . "\n$1",
                $content
            );
            $modified = true;
        }

        if (!empty($newRouteLines)) {
            $content = preg_replace(
                '/(\/\/\-INSTALL_POINT_ADD_ROUTES)/',
                implode("\n    ", $newRouteLines) . "\n    $1",
                $content
            );
            $modified = true;
        }

        if (!empty($newValueLines)) {
            $content = preg_replace(
                '/(\/\/\-INSTALL_POINT_ADD_VALUES)/',
                implode("\n    ", $newValueLines) . "\n    $1",
                $content
            );
            $modified = true;
        }

        if ($modified) {
            file_put_contents($routesPath, $content);
        }
    }

    private function removeRoutes(string $dirModule): void
    {
        $routesFile = $dirModule . DS . self::PATH_MODULE_ROUTES;
        $targetPath = BASE_DIRECTORY . self::PATH_ROUTES;

        if (!file_exists($routesFile) || !file_exists($targetPath)) {
            return;
        }

        include $routesFile;
        $content = file_get_contents($targetPath);

        if (isset($installUses)) {
            foreach (explode("\n", trim($installUses)) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $pattern = '/^[ \t]*' . preg_quote($line, '/') . '\s*[\r\n]?/m';
                    $content = preg_replace($pattern, '', $content);
                }
            }
        }

        if (isset($installRoutes)) {
            foreach (explode("\n", trim($installRoutes)) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $pattern = '/^[ \t]*' . preg_quote($line, '/') . '\s*[\r\n]?/m';
                    $content = preg_replace($pattern, '', $content);
                }
            }
        }

        if (isset($installValues)) {
            foreach (explode("\n", trim($installValues)) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $pattern = '/^[ \t]*' . preg_quote($line, '/') . '\s*[\r\n]?/m';
                    $content = preg_replace($pattern, '', $content);
                }
            }
        }

        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        file_put_contents($targetPath, $content);
    }

    private function copyRecursive(string $source, string $destination): void
    {
        $source = rtrim($source, DS);
        $destination = rtrim($destination, DS);

        if (!is_dir($source)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $destination . DS . substr($item->getPathname(), strlen($source) + 1);

            if ($item->isDir()) {
                $this->ensureDir($targetPath);
            } else {
                $this->safeCopy($item->getPathname(), $targetPath);
            }
        }
    }

    private function safeCopy(string $src, string $dst): void
    {
        $this->ensureDir(dirname($dst));
        copy($src, $dst);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function updateTranslations(string $key, string $value): void
    {
        $envPath = BASE_DIRECTORY . '.env';

        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath);
        $found = false;

        foreach ($lines as &$line) {
            if (str_starts_with(trim($line), "$key=")) {
                $line = "$key=$value\n";
                $found = true;
                break;
            }
        }

        if (!$found) {
            $lines[] = "$key=$value\n";
        }

        file_put_contents($envPath, implode('', $lines));
    }

    /**
     * INFO! Metoda nie używana. Można dodać tłumaczenia, ale najpierw dopracuj waitForModuleState()?
     */
    private function translation(string $trans): string
    {
        $translation = new TranslationLoader();

        if (!empty($translation->load()->arrayTranslation)) {
            $arrayTrans = $translation->load()->arrayTranslation;

            if (array_key_exists($trans, $arrayTrans)) {
                return $arrayTrans[$trans];
            }
        }

        return $trans;
    }

    private function backupBaseFiles(array $arrayFiles, array $arrayTargets): void
    {
        $allowedDirs = ['/src/', '/templates/'];

        // Filtrowanie arrayFiles po ścieżkach źródłowych
        $arrayFiles = array_filter($arrayFiles, function ($srcRel) use ($allowedDirs) {
            foreach ($allowedDirs as $dir) {
                if (str_contains($srcRel, $dir)) {
                    return true;
                }
            }
            return false;
        });

        foreach ($arrayFiles as $key => $srcRel) {
            if (!isset($arrayTargets[$key])) {
                continue;
            }

            $src = BASE_DIRECTORY . $srcRel;
            $target = BASE_DIRECTORY . $arrayTargets[$key];
            $backup = BASE_DIRECTORY . self::PATH_BACKUP_BASE . DS . $arrayTargets[$key];

            if (is_file($src) && file_exists($target) && !file_exists($backup)) {
                $this->safeCopy($target, $backup);
            } elseif (is_dir($src)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    $relative = substr($file->getPathname(), strlen($src) + 1);
                    $targetFile = $target . DS . $relative;
                    $backupFile = $backup . DS . $relative;
                    if (file_exists($targetFile)) {
                        $this->safeCopy($targetFile, $backupFile);
                    }
                }
            }
        }

        // Kopia application/routes.php
        $routesPath = BASE_DIRECTORY . self::PATH_ROUTES;
        $routesBackup = BASE_DIRECTORY . self::PATH_BACKUP_BASE . DS . self::PATH_ROUTES;
        if (file_exists($routesPath) && !file_exists($routesBackup)) {
            $this->safeCopy($routesPath, $routesBackup);
        }

        // Kopia src/Config/ConstantConfig.php
        $constantPath = BASE_DIRECTORY . self::PATH_CONSTANT;
        $constantBackup = BASE_DIRECTORY . self::PATH_BACKUP_BASE . DS . self::PATH_CONSTANT;
        if (file_exists($constantPath) && !file_exists($constantBackup)) {
            $this->safeCopy($constantPath, $constantBackup);
        }
    }

    private function restoreBaseFiles(array $arrayFiles, array $arrayTargets): void
    {
        foreach ($arrayFiles as $key => $item) {
            if (!isset($arrayTargets[$key])) {
                continue;
            }

            $target = BASE_DIRECTORY . $arrayTargets[$key];
            $backup = BASE_DIRECTORY . self::PATH_BACKUP_BASE . DS . $arrayTargets[$key];

            if (is_file($backup)) {
                $this->safeCopy($backup, $target);
            } elseif (is_dir($backup)) {
                $this->copyRecursive($backup, $target);
            }
        }

        // Restore application/routes.php
        $routesTarget = BASE_DIRECTORY . self::PATH_ROUTES;
        $routesBackup = BASE_DIRECTORY . self::PATH_BACKUP_BASE . DS . self::PATH_ROUTES;
        if (file_exists($routesBackup)) {
            $this->safeCopy($routesBackup, $routesTarget);
        }

        // Restore src/Config/ConstantConfig.php
        $constantTarget = BASE_DIRECTORY . self::PATH_CONSTANT;
        $constantBackup = BASE_DIRECTORY . self::PATH_BACKUP_BASE . DS . self::PATH_CONSTANT;
        if (file_exists($constantBackup)) {
            $this->safeCopy($constantBackup, $constantTarget);
        }
    }
}
