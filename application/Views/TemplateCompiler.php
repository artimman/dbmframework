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

namespace Dbm\Views;

/**
 * TemplateCompiler — kompiluje składnię szablonu (.phtml-like) do czystego PHP.
 *
 * Obsługuje dyrektywy typu {% block %}, {% include %}, {{ variable|filter }},
 * {% if %}, {% for %}, itp. Kolejność reguł ma kluczowe znaczenie.
 */
class TemplateCompiler
{
    /** @var string Ścieżka bazowa katalogu z szablonami */
    private string $templatesPath;

    /** @var TemplateFilters Obiekt filtrów do przetwarzania wyrażeń z potokami */
    private TemplateFilters $filters;

    /** @var bool Włącza zapis debugowej wersji skompilowanego pliku */
    private bool $enableDebugger = false;

    /**
     * @param string $templatesPath Ścieżka do katalogu z szablonami
     * @param TemplateFilters $filters Instancja filtrów
     */
    public function __construct(string $templatesPath, TemplateFilters $filters)
    {
        $this->templatesPath = rtrim(str_replace(['/', '\\'], DS, $templatesPath), DS) . DS;
        $this->filters = $filters;
    }

    /**
     * Kompiluje wskazany szablon do kodu PHP (zwraca wynikowy tekst).
     *
     * @param string $templateName Nazwa szablonu (np. 'main.phtml')
     * @return string Zawartość skompilowanego pliku PHP
     * @throws TemplateException Jeśli plik nie istnieje lub nie można go odczytać
     */
    public function compile(string $templateName): string
    {
        $templateFile = $this->templatesPath . ltrim(str_replace(['/', '\\'], DS, $templateName), DS);

        if (!file_exists($templateFile)) {
            throw new TemplateException("Template file not found: {$templateFile}");
        }

        $rawContent = file_get_contents($templateFile);
        if ($rawContent === false) {
            throw new TemplateException("Unable to read template: {$templateFile}");
        }

        $compiled = $this->compileSyntax($rawContent);

        // Opcjonalny tryb debug – zapisuje skompilowany plik dla podglądu
        if ($this->enableDebugger) {
            $debugDir = BASE_DIRECTORY . 'var' . DS . 'cache' . DS . 'debug';
            if (!is_dir($debugDir) && !mkdir($debugDir, 0777, true) && !is_dir($debugDir)) {
                throw new TemplateException("Unable to create debug directory: {$debugDir}");
            }

            $debugFile = $debugDir . DS . basename($templateName, '.phtml') . '_debug.php';
            file_put_contents($debugFile, $compiled);
        }

        return $compiled;
    }

    /**
     * Zwraca pełną ścieżkę katalogu z szablonami.
     *
     * @return string
     */
    public function getTemplatesPath(): string
    {
        return $this->templatesPath;
    }

    /**
     * Zwraca obiekt filtrów używany przez kompilator.
     *
     * @return TemplateFilters
     */
    public function getFilters(): TemplateFilters
    {
        return $this->filters;
    }

    /**
     * Główna metoda parsowania składni szablonowej na kod PHP.
     * Uwaga: kolejność reguł ma znaczenie!
     *
     * @param string $content Surowa treść szablonu
     * @return string Przetworzony kod PHP
     */
    private function compileSyntax(string $content): string
    {
        // 0) Normalizacja końców linii
        $content = str_replace("\r\n", "\n", trim($content));

        // 1) Usunięcie komentarzy {# ... #}
        $content = preg_replace('/\{\#.*?\#\}/su', '', $content);

        // 2) {% extends '...' %}
        $content = preg_replace_callback(
            '/\{\%\s*extends\s+[\'"]([^\'"]+)[\'"]\s*\%\}/iu',
            fn ($m) => "<?php \$this->extend('".addslashes($m[1])."'); ?>",
            $content
        );

        // 3) {% block name %} / {% endblock %}
        $content = preg_replace_callback(
            '/\{\%\s*block\s+([a-zA-Z0-9_]+)\s*\%\}/iu',
            fn ($m) => "<?php \$this->startBlock('{$m[1]}'); ?>",
            $content
        );
        $content = preg_replace('/\{\%\s*endblock\s*\%\}/iu', "<?php \$this->endBlock(); ?>", $content);

        // 4) {% yield name %}
        $content = preg_replace_callback(
            '/\{\%\s*yield\s+([a-zA-Z0-9_]+)\s*\%\}/iu',
            fn ($m) => "<?php echo \$this->yieldBlock('{$m[1]}'); ?>",
            $content
        );

        // 5) {% include 'tpl' with {...} %}
        $content = preg_replace_callback(
            '/\{\%\s*include\s+[\'"]([^\'"]+)[\'"]\s+with\s+(\{.*?\})\s*\%\}/su',
            function ($m) {
                $tpl = addslashes($m[1]);
                $vars = preg_replace('/([\'"])([a-zA-Z0-9_]+)\1\s*:\s*/u', "'$2' => ", $m[2]);
                $vars = str_replace(['{', '}'], ['[', ']'], $vars);
                return "<?php echo \$this->renderPartial('{$tpl}', {$vars}); ?>";
            },
            $content
        );

        // 6) {% include 'tpl' %}
        $content = preg_replace_callback(
            '/\{\%\s*include\s+[\'"]([^\'"]+)[\'"]\s*\%\}/iu',
            fn ($m) => "<?php echo \$this->renderPartial('".addslashes($m[1])."'); ?>",
            $content
        );

        // 7) {{{ expr }}} → raw echo
        $content = preg_replace('/\{\{\{\s*(.*?)\s*\}\}\}/su', "<?php echo $1; ?>", $content);

        // 8) {{ expr|filters }} — potoki filtrów
        $content = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/su', function ($m) {
            $expr = trim($m[1]);

            // Filtry pipeline
            if (strpos($expr, '|') !== false) {
                $parts = array_map('trim', explode('|', $expr));
                $main = array_shift($parts);

                if (end($parts) === 'raw') {
                    array_pop($parts);
                    return "<?php echo " . $this->filters->applyPhp($main, $parts) . "; ?>";
                }

                return "<?php echo " . $this->filters->applyPhp($main, $parts) . "; ?>";
            }

            // Nieescape'owane wywołania $this->...
            if (preg_match('/^\$this->\s*[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $expr)) {
                return "<?php echo {$expr}; ?>";
            }

            // Zabezpieczenie dla $expr, które zawiera / nie zawiera $ (name, $name, $user['name'])
            if (
                !preg_match('/^\$/', $expr) && // nie zaczyna się od $
                !preg_match('/[\'"]/', $expr) && // nie zawiera stringów
                !preg_match('/^[a-zA-Z_]\w*\s*\(/', $expr) // nie jest wywołaniem funkcji (np. ucfirst(...))
            ) {
                $expr = '$' . $expr;
            }

            // Domyślnie — bezpieczny htmlspecialchars
            return '<?php echo htmlspecialchars((string)(' . $expr . ' ?? ""), ENT_QUOTES, "UTF-8"); ?>';
        }, $content);

        // 9) {% if / elseif / else / endif %}
        $content = preg_replace('/\{\%\s*if\s*\((.*?)\)\s*(?:\:)?\s*\%\}/isu', "<?php if ($1): ?>", $content);
        $content = preg_replace('/\{\%\s*elseif\s*\((.*?)\)\s*(?:\:)?\s*\%\}/isu', "<?php elseif ($1): ?>", $content);
        $content = preg_replace('/\{\%\s*else\s*(?:\:)?\s*\%\}/iu', "<?php else: ?>", $content);
        $content = preg_replace('/\{\%\s*endif\s*(?:;)?\s*\%\}/iu', "<?php endif; ?>", $content);

        // 10) {% foreach ... %} / {% endforeach %}
        $content = preg_replace_callback(
            '/\{\%\s*foreach\s*(?:\(\s*(.*?)\s*\)|\s*(.*?)\s*)\s*:\s*\%\}/isu',
            fn ($m) => "<?php foreach (" . ($m[1] ?: $m[2]) . "): ?>\n",
            $content
        );
        $content = preg_replace('/\{\%\s*endforeach\s*(?:;)?\s*\%\}/iu', "<?php endforeach; ?>\n", $content);

        // 11) {% for ... %} / {% endfor %}
        $content = preg_replace('/\{\%\s*for\s*(.*?)\s*\:\s*\%\}/isu', "<?php for ($1): ?>\n", $content);
        $content = preg_replace('/\{\%\s*endfor\s*(?:;)?\s*\%\}/iu', "<?php endfor; ?>\n", $content);

        // 12) {% echo ... %}
        $content = preg_replace_callback(
            '/\{\%\s*echo\s+(.*?)\s*\%\}/isu',
            fn ($m) => "<?php echo {$m[1]}; ?>",
            $content
        );

        // 13) Fallback: dowolny {% ... %} (np. PHP logic)
        $content = preg_replace_callback('/\{\%\s*(.*?)\s*\%\}/su', function ($m) {
            $code = trim($m[1]);
            if ($code === '') {
                return '';
            }
            if (!str_ends_with($code, ';')) {
                $code .= ';';
            }
            return "<?php {$code} ?>";
        }, $content);

        // 14) Wyrównanie bloków PHP (usuwa znaczniki PHP bez przerwy linii)
        $content = preg_replace('/\?\>[ \t]*\<\?php/', "\n", $content);

        return $content;
    }
}
