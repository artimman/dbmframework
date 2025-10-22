<?php
/**
 * Library: DbM Search Engine
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Lib\Search\Src\Classes;

/**
 * Helper dla operacji na tekście w wynikach wyszukiwania.
 */
class SearchHelper
{
    // Stałe
    private const LIMIT = 250; // default 250 characters
    private const ENDING = '...'; // default ellipsis

    /**
     * Czyszczenie zawartości
     */
    public static function clearContent(string $content): string
    {
        $content = str_replace(["\n", "\r"], '', $content);
        return trim(strip_tags($content));
    }

    /**
     * Przycinanie treści do określonej długości, z zachowaniem tagów <mark>.
     *
     * - Usuwa wszystkie inne znaczniki HTML, zostawiając jedynie <mark>
     * - Dekoduje encje HTML (&amp; -> &)
     * - Przycięcie odbywa się do pełnego słowa (nie urywa w środku wyrazu)
     * - Jeśli tekst przekracza limit, dodaje końcówkę (np. "...")
     *
     * @param string $content Treść do skrócenia
     * @param int $limit Maksymalna długość (domyślnie 250)
     * @param string $ending Końcówka dodawana po skróceniu (domyślnie "…")
     * @return string Skrócony i oczyszczony tekst
     *
     * Przykład:
     * $short = SearchHelper::safeTruncate($content, 160);
     */
    public static function safeTruncate(string $content, int $limit = self::LIMIT, string $ending = self::ENDING): string
    {
        $content = htmlspecialchars_decode($content, ENT_QUOTES);
        $content = trim(strip_tags($content, '<mark>'));

        // Przycięcie do pełnego słowa
        if (mb_strlen($content) > $limit) {
            $content = trim(preg_replace('~\s+\S+$~u', '', mb_substr($content, 0, $limit)));
            $content .= $ending;
        }

        return $content;
    }

    /**
     * Podświetlanie wyszukiwanej frazy w tekście.
     *
     * - Ucieka cały tekst do bezpiecznego HTML-a (htmlspecialchars)
     * - Następnie zamienia każde wystąpienie frazy na <mark>fraza</mark>
     * - Wyszukiwanie jest case-insensitive i wspiera UTF-8
     *
     * @param string $content Tekst źródłowy
     * @param string $text Fraza do podświetlenia
     * @return string Tekst z podświetleniem
     *
     * Przykład:
     * $highlighted = SearchHelper::highlightText($content, $query);
     */
    public static function highlightText(string $content, string $text): string
    {
        if (!$text) {
            return htmlspecialchars($content);
        }

        return preg_replace(
            '/' . preg_quote($text, '/') . '/iu',
            '<mark>$0</mark>',
            htmlspecialchars($content)
        );
    }

    /**
     * Połączenie highlight + truncate.
     *
     * Najpierw podświetla frazę, a następnie skraca tekst do pełnego słowa.
     *
     * @param string $content Tekst źródłowy
     * @param string $text Fraza do podświetlenia
     * @param int $limit Maksymalna długość (domyślnie 250)
     * @param string $ending Końcówka po skróceniu (domyślnie "…")
     * @return string Skrócony i podświetlony tekst
     *
     * Przykład:
     * $final = SearchHelper::highlightAndTruncate($content, $query, 160);
     */
    public static function highlightAndTruncate(string $content, string $text, int $limit = self::LIMIT, string $ending = self::ENDING): string
    {
        $contentConvert = self::highlightText($content, $text);
        return self::safeTruncate($contentConvert, $limit, $ending);
    }
}
