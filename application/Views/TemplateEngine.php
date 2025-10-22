<?php
/**
 * Application: DbM Framework + DbM Template Engine (views for framework)
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * Prekompilacja wszystkich szablonów (wersja testowa) - skanuje /templates, kompiluje wszystkie .phtml, pokazuje raport.
 * Komenda bash: php application/console.php TemplateCompile
 */

declare(strict_types=1);

namespace Dbm\Views;

use Dbm\Classes\BaseController;
use Dbm\Classes\Http\Response;
use Dbm\Classes\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * TemplateEngine
 *
 * Klasa odpowiedzialna za renderowanie, kompilację i cache'owanie szablonów.
 * Obsługuje filtry, debugowanie, linting i dziedziczenie layoutów.
 */
class TemplateEngine extends TemplateFeature
{
    private const PATH_TEMPLATES = BASE_DIRECTORY . 'templates';
    private const PATH_CACHE = BASE_DIRECTORY . 'var' . DS . 'cache';
    private const PATH_DEBUG = BASE_DIRECTORY . 'var' . DS . 'cache' . DS . 'debug';

    private TemplateCompiler $compiler;
    private TemplateCache $cache;
    private TemplateFilters $filters;

    private bool $enableDebugger = false; // Default: false, optionally enable debugger (for tests)
    private bool $enableLint = false; // Default: false for Windows, can be changed to true for Linux
    private bool $cacheEnabled = true;
    private string $templatesPath;
    private string $cachePath;

    /**
     * @param string $templatesPath Ścieżka do katalogu z szablonami.
     * @param string $cachePath Ścieżka do katalogu cache.
     */
    public function __construct(
        string $templatesPath = self::PATH_TEMPLATES,
        string $cachePath = self::PATH_CACHE
    ) {
        $this->templatesPath = rtrim($templatesPath, DS) . DS;
        $this->cachePath = rtrim($cachePath, DS) . DS;

        $this->filters = new TemplateFilters();
        $this->compiler = new TemplateCompiler($this->templatesPath, $this->filters);
        $this->cache = new TemplateCache($this->cachePath);

        // Odczyt flagi cache z ENV
        $this->cacheEnabled = filter_var(getenv('CACHE_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Renderuje szablon i zwraca treść lub ResponseInterface.
     *
     * @param string $template Nazwa pliku szablonu.
     * @param array $data Dane przekazywane do szablonu.
     * @param bool $asResponse Czy zwrócić obiekt ResponseInterface.
     * @return \Dbm\Classes\Http\Response|ResponseInterface|string
     */
    public function render(string $template, array $data = [], bool $asResponse = true): ResponseInterface|string
    {
        $controllerContext = ($this instanceof BaseController) ? $this : null;
        $content = $this->renderContent($template, $data, $controllerContext);

        if ($asResponse) {
            $stream = new Stream($content);
            return new Response(200, ['Content-Type' => 'text/html'], $stream);
        }

        return $content;
    }

    /**
     * Kompiluje, ładuje i renderuje szablon (z obsługą layoutów).
     *
     * @param string $template
     * @param array $data
     * @param BaseController|null $controller
     * @return string
     * @throws TemplateException
     */
    public function renderContent(string $template, array $data = [], ?BaseController $controller = null): string
    {
        $templateFile = $this->templatesPath . ltrim(str_replace(['/', '\\'], DS, $template), DS);
        $cachePath = $this->cache->getCachePath($template);

        if (!$this->cacheEnabled || !$this->cache->isFresh($templateFile, $cachePath)) {
            $body = $this->compiler->compile($template);
            $this->enableDebugger($template, $body);
            $this->writeCompiledTemplate($template, $cachePath, $body);
        }

        if (!file_exists($cachePath)) {
            throw new TemplateException("Compiled template not found: {$cachePath}");
        }

        require_once $cachePath;

        $className = $this->getCompiledClassName($template);
        if (!class_exists($className)) {
            throw new TemplateException("Compiled template class {$className} not found in {$cachePath}");
        }

        /** @var TemplateRuntime $tpl */
        $tpl = new $className();
        $tpl->engine = $this;
        $tpl->controller = $controller ?? (($this instanceof BaseController) ? $this : null);
        $tpl->setData($data);

        $output = $tpl->render($data);
        $data = array_merge($data, $tpl->data ?? []);

        if (!empty($tpl->parent)) {
            $output = $this->renderParentTemplate($tpl->parent, $data, $controller, $tpl->blocks ?? []);
        }

        return $output;
    }

    /** @param bool $enable */
    public function setEnableDebugger(bool $enable): void
    {
        $this->enableDebugger = $enable;
    }

    /** @param bool $enabled */
    public function setEnableLint(bool $enabled): void
    {
        $this->enableLint = $enabled;
    }

    /** @param bool $enabled */
    public function setCacheEnabled(bool $enabled)
    {
        $this->cacheEnabled = $enabled;
    }

    /** @param string $name @param callable $generator */
    public function addFilter(string $name, callable $generator): void
    {
        $this->filters->register($name, $generator);
    }

    /**
     * Fabryka: Tworzy silnik z gotowych komponentów (przydatne w testach / DI).
     *
     * @param TemplateCompiler $compiler
     * @param TemplateCache $cache
     */
    public static function createFromComponents(
        TemplateCompiler $compiler,
        TemplateCache $cache
    ): self {
        $engine = new self($compiler->getTemplatesPath(), $cache->getCacheDir());
        $engine->compiler = $compiler;
        $engine->cache = $cache;
        $engine->filters = $compiler->getFilters();

        return $engine;
    }

    /**
     * Czyszczenie cache
     */
    protected function clearCache(): void
    {
        foreach (glob(self::PATH_CACHE . '*') as $file) {
            @unlink($file);
        }
    }

    /**
     * Kompiluje i zapisuje plik klasy PHP dla szablonu.
     *
     * @param string $template
     * @param string $cachePath
     * @param string $body
     * @throws TemplateException
     */
    private function writeCompiledTemplate(string $template, string $cachePath, string $body): void
    {
        $className = $this->getCompiledClassName($template);
        $classCode = $this->generateClass($className, $body);
        $tmpFile = $cachePath . '.tmp_' . uniqid('', true);

        file_put_contents($tmpFile, $classCode);

        if ($this->enableLint) {
            $this->runPhpLintOnFile($tmpFile);
        }

        rename($tmpFile, $cachePath);
    }

    /**
     * Renderuje layout rodzica.
     *
     * @param string $parentTpl
     * @param array $data
     * @param BaseController|null $controller
     * @param array $childBlocks
     * @return string
     */
    private function renderParentTemplate(string $parentTpl, array $data, ?BaseController $controller, array $childBlocks): string
    {
        $parentCache = $this->cache->getCachePath($parentTpl);
        $parentFile = $this->templatesPath . ltrim(str_replace(['/', '\\'], DS, $parentTpl), DS);

        if (!$this->cache->isFresh($parentFile, $parentCache)) {
            $body = $this->compiler->compile($parentTpl);
            $this->writeCompiledTemplate($parentTpl, $parentCache, $body);
        }

        require_once $parentCache;
        $classParent = '__Tpl_' . sha1($parentTpl);

        /** @var TemplateRuntime $parentInstance */
        $parentInstance = new $classParent();
        $parentInstance->engine = $this;
        $parentInstance->controller = $controller;
        $parentInstance->blocks = array_merge($parentInstance->blocks ?? [], $childBlocks);

        return $parentInstance->render($data);
    }

    /**
     * Generuje klasę PHP dla szablonu.
     */
    private function generateClass(string $className, string $body): string
    {
        return <<<PHP
<?php
if (!class_exists('{$className}')) {
    class {$className} extends \\Dbm\\Views\\TemplateRuntime {
        public function render(array \$data = []): string {
            extract((array)\$data, EXTR_SKIP);
            ob_start(); ?>
{$body}
<?php
            return (string) ob_get_clean();
        }
    }
}
PHP;
    }

    /**
     * Sprawdza poprawność składni PHP w pliku.
     *
     * @throws TemplateException
     */
    private function runPhpLintOnFile(string $filePath): void
    {
        $php = defined('PHP_BINARY') ? PHP_BINARY : null;

        if (!$php || !is_executable($php)) {
            throw new TemplateException('Unable to locate PHP binary for linting.');
        }

        $cmd = escapeshellarg($php) . ' -l ' . escapeshellarg($filePath) . ' 2>&1';
        exec($cmd, $output, $retval);

        if ($retval !== 0) {
            @unlink($filePath);
            throw new TemplateException("PHP syntax check failed:\n" . implode("\n", $output));
        }
    }

    /**
     * Zapisuje plik debug w katalogu PATH_DEBUG (jeśli aktywny debugger).
     */
    private function enableDebugger(string $template, string $body): void
    {
        if (!$this->enableDebugger) {
            return;
        }

        if (!is_dir(self::PATH_DEBUG)) {
            mkdir(self::PATH_DEBUG, 0777, true);
        }

        $key = str_replace(['/', '\\', '.'], '_', ltrim($template, '/'));
        $debugFile = self::PATH_DEBUG . DS . $key . '_debug.php';

        if (file_put_contents($debugFile, $body) === false) {
            throw new TemplateException("Failed to write debug file: {$debugFile}");
        }
    }

    /**
     * Generuje nazwy klas
     */
    private function getCompiledClassName(string $template): string
    {
        return '__Tpl_' . sha1($template);
    }

    /**
     * Automatyczna detekcja bezpiecznych metod z TemplateFeature.
     *
     * @return string[]
     */
    private function detectSafeHelpers(): array
    {
        $classes = [TemplateFeature::class];
        $methods = [];

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if (!str_starts_with($m->getName(), '__')) {
                    $methods[] = $m->getName();
                }
            }
        }

        return array_unique($methods);
    }
}
