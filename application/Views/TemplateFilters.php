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
 * TemplateFilters — rejestr i kompilator filtrów używanych w składni szablonów.
 *
 * Każdy filtr jest funkcją generującą kod PHP, np.:
 *   {{ title|upper }}   →  echo strtoupper($title);
 *   {{ html|raw_allowed(['b','br']) }}
 *   {{ date|date('Y-m-d') }}
 */
class TemplateFilters
{
    /**
     * @var array<string, callable> Lista zarejestrowanych filtrów.
     * Każdy filtr otrzymuje string $expr i zwraca kod PHP, np. fn($v) => "strtoupper({$v})".
     */
    private array $filters = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * Rejestruje nowy filtr lub nadpisuje istniejący.
     *
     * @param string $name Nazwa filtra
     * @param callable $phpGenerator Funkcja przyjmująca $expr i zwracająca kod PHP (string)
     */
    public function register(string $name, callable $phpGenerator): void
    {
        $this->filters[$name] = $phpGenerator;
    }

    /**
     * Sprawdza, czy filtr istnieje.
     */
    public function exists(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    /**
     * Generuje wyrażenie PHP z zastosowanymi filtrami.
     *
     * @param string $expr Wyrażenie bazowe (np. "$var")
     * @param array $filters Lista filtrów np. ['upper', 'escape']
     * @return string Gotowy fragment PHP, np. "htmlspecialchars(strtoupper($var))"
     */
    public function applyPhp(string $expr, array $filters): string
    {
        foreach ($filters as $filterDef) {
            $filterDef = trim($filterDef);
            if ($filterDef === '') {
                continue;
            }

            // Obsługa filtrów z parametrami: e.g. date("Y-m-d")
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', $filterDef, $m)) {
                $filter = $m[1];
                $args = trim($m[2]);

                // Rejestr istniejącego filtra
                if (isset($this->filters[$filter])) {
                    $generator = $this->filters[$filter];
                    $expr = $generator($expr, $args);
                    continue;
                }

                // Fallback do metody runtime (np. $this->customFilter($expr, args))
                $expr = "\$this->{$filter}({$expr}" . ($args !== '' ? ', ' . $args : '') . ")";
                continue;
            }

            // Bez parametrów
            if (isset($this->filters[$filterDef])) {
                $generator = $this->filters[$filterDef];
                $expr = $generator($expr);
            } else {
                // Fallback do metody z TemplateRuntime
                $expr = "\$this->{$filterDef}({$expr})";
            }
        }

        return $expr;
    }

    /**
     * Rejestruje zestaw wbudowanych filtrów (można nadpisać przez register()).
     */
    private function registerDefaults(): void
    {
        $this->filters = [
            // Filtry podstawowe
            'e' => fn (string $v): string => "htmlspecialchars({$v}, ENT_QUOTES, 'UTF-8')",
            'raw' => fn (string $v): string => $v,
            // Filtry proste
            'upper' => fn (string $v): string => "strtoupper({$v})",
            'lower' => fn (string $v): string => "strtolower({$v})",
            'trim' => fn (string $v): string => "trim({$v})",
            'length' => fn (string $v): string => "strlen({$v})",
            'nl2br' => fn (string $v): string => "nl2br({$v})",
            'strip' => fn (string $v): string => "strip_tags({$v})",
            // Filtry rozszerzenie
            'json' => fn (string $v): string => "json_encode({$v}, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)",
            'url' => fn (string $v): string => "urlencode({$v})",
            'join'  => fn (string $v, string $args = "','"): string => "implode({$args}, (array)({$v}))",
            'number' => fn (string $v, string $args = "0, '.', ' '"): string => "number_format((float)({$v}), {$args})",
            // Filtr daty: |date('Y-m-d H:i')
            'date' => fn (string $v, string $args = "'Y-m-d H:i'"): string => "date({$args}, is_numeric({$v}) ? (int){$v} : strtotime((string){$v}))",
            // raw_allowed(['br', 'b', 'i'])
            'raw_allowed' => fn (string $v, string $args = "[]"): string =>
                "strip_tags({$v}, '<' . implode('><', (array){$args}) . '>')",
        ];
    }
}
