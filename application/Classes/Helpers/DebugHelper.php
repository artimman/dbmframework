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

namespace Dbm\Classes\Helpers;

class DebugHelper
{
    public static function dump(mixed $var): void
    {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();

        // Konwersja znaków specjalnych do HTML
        $output = htmlspecialchars(trim($output), ENT_QUOTES, 'UTF-8');

        // Formatowanie linii i spacji
        $output = str_replace('=&gt;' . PHP_EOL, '', $output);
        $output = preg_replace_callback(
            '/\s+(\b(?:object|array|string|int|bool|float|double|null|uninitialized|resource)\b\()/',
            function ($matches) { return ' ' . $matches[1]; },
            $output
        );

        // Formatowanie danych
        $output = preg_replace('/\b(array)\((\d+)\)/', '<span class="item">$1($2)</span>', $output);
        $output = preg_replace('/object\((.*?)\)#(\d+) \((\d+)\)/', '<span class="item">object($1)#$2 ($3)</span>', $output);
        $output = preg_replace('/\b(uninitialized|resource)\((.*?)\)/', '<span class="other">$1($2)</span>', $output);
        $output = preg_replace('/\b(string|int|bool|float|double|null)\((\d+)\)/', '<span class="type">$1($2)</span>', $output);
        $output = preg_replace('/\[&quot;(.*?)&quot;\]/', '<span class="key"><span class="special">#</span>$1<span class="special">:</span></span>', $output);
        $output = preg_replace('/&quot;(.*?)&quot;/', '<span class="string">&quot;$1&quot;</span>', $output);
        $output = preg_replace('/(&quot;)/', '<span class="special">$0</span>', $output);
        $output = preg_replace('/([{}\[\]])/', '<span class="special">$1</span>', $output);
        $output = preg_replace('/\b(\d+)\b/', '<span class="number">$1</span>', $output);

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <title>DbM Framework - Output Debugger</title>
            <style>
                body { margin: 0; padding: 2rem; font-family: Arial, sans-serif, monospace; font-size: 16px; background: #23241f; color: #f8f8f2; }
                .container { background: #272822; padding: 1rem; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5); overflow-x: auto; }
                .header { float: right; color: #aba8a8; font-size: 12px; margin-bottom: 10px; font-weight: bold; }
                .output { white-space: pre-wrap; word-wrap: break-word; }
                .item { color: #1299da; }
                .key { color: #aba8a8; }
                .special { color: #ff8403; }
                .string { color: #35d43a; }
                .number { color: #00ffff; }
                .type { color: #ffff00; }
                .other { color: #b229d9; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">DbM Framework - Output Debugger</div>
                <pre class="output">$output</pre>
            </div>
        </body>
        </html>
        HTML;

        exit;
    }
}
