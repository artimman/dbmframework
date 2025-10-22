<?php
/**
 * Library: DbM DataTables PHP
 * A class designed for the DbM Framework and for use in any PHP application.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Lib\DataTables\Src\Classes;

use Lib\DataTables\Src\Utility\Translator;

/**
 * Provides built-in rendering logic for special cell types (action buttons, status, images, etc.).
 *
 * This class is stateless and contains only static helper methods.
 * It is used internally by DataTableRenderer, but can be extended or overridden if needed.
 */
class CellRenderer
{
    private const ARRAY_SPECIAL_CELLS = [
        'cell_action' => 'renderActionCell',
        'cell_status' => 'renderStatusCell',
        'cell_change_status' => 'renderChangeStatusCell',
        'cell_image' => 'renderImageCell',
    ];

    public static function specialCellSupports(string $tag): bool
    {
        return isset(self::ARRAY_SPECIAL_CELLS[$tag]);
    }

    public static function renderSpecialCell(array $col, array $record): string
    {
        $options = $col['tag_options'] ?? [];
        $field = $col['field'] ?? null;

        return match ($col['tag']) {
            'cell_action' => self::renderActionCell($record, $options, $field),
            'cell_image' => self::renderImageCell($record, $options, $field),
            'cell_status' => self::renderStatusCell($record, $field),
            'cell_change_status' => self::renderChangeStatusCell($record, $field),
            default => '',
        };
    }

    private static function renderActionCell(array $record, array $options = []): string
    {
        $actions = $options['actions'] ?? [];

        $html = PHP_EOL . '<div class="dropdown">' . PHP_EOL;
        $html .= '    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">' . PHP_EOL;
        $html .= '        <i class="bi bi-three-dots-vertical"></i>' . PHP_EOL;
        $html .= '    </button>' . PHP_EOL;
        $html .= '    <ul class="dropdown-menu">'. PHP_EOL;

        foreach ($actions as $action) {
            // zamień {id} i inne pola w url/attrs na wartości z $record
            $url = $action['url'] ?? '#';
            $url = preg_replace_callback('/\{(\w+)\}/', fn ($m) => $record[$m[1]] ?? '', $url);

            $label = htmlspecialchars($action['label'] ?? '');
            $icon  = htmlspecialchars($action['icon'] ?? '');
            $class = htmlspecialchars($action['class'] ?? '');

            if ($action['type'] === 'link') {
                $html .= sprintf(
                    '        <li><a href="%s" class="dropdown-item %s"><i class="%s me-2"></i>%s</a></li>' . PHP_EOL,
                    htmlspecialchars($url),
                    $class,
                    $icon,
                    $label
                );
            } elseif ($action['type'] === 'button') {
                $attrs = '';
                foreach (($action['attrs'] ?? []) as $k => $v) {
                    $v = preg_replace_callback('/\{(\w+)\}/', fn ($m) => $record[$m[1]] ?? '', $v);
                    $attrs .= sprintf(' %s="%s"', $k, htmlspecialchars($v));
                }
                $html .= sprintf(
                    '        <li><button type="button" class="dropdown-item %s"%s><i class="%s me-2"></i>%s</button></li>' . PHP_EOL,
                    $class,
                    $attrs,
                    $icon,
                    $label
                );
            }
        }

        $html .= '    </ul>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;

        return $html . '    ';
    }

    private static function renderStatusCell(array $record, ?string $field = null): string
    {
        $field  = $field ?? 'status';
        $status = strtolower((string)($record[$field] ?? ''));

        $mapClass = [
            'active'   => 'success',
            'inactive' => 'danger',
            'new'      => 'warning',
        ];
        $mapLabel = [
            'active'   => 'active',
            'inactive' => 'inactive',
            'new'      => 'new',
            'unknown'  => 'unknown',
        ];

        $cls = $mapClass[$status] ?? 'secondary';
        $labelKey = $mapLabel[$status] ?? 'unknown';
        $extraClass = $status === 'new' ? ' text-dark' : '';

        return sprintf(
            '<span class="badge bg-%s%s">%s</span>',
            $cls,
            $extraClass,
            Translator::trans($labelKey)
        );
    }

    private static function renderChangeStatusCell(array $record, ?string $field = null): string
    {
        $field = $field ?? 'status';
        $status = $record[$field] ?? 'default';
        $id = $record['id'] ?? null;
        $arrayStatus = ['A' => "active", 'I' => "inactive", 'N' => 'new'];

        if (in_array($status, $arrayStatus) && !empty($id)) {
            $links = [
                'active' => [
                    'url' => "?id=$id&status={$arrayStatus['I']}",
                    'title' => Translator::trans('deactivate'),
                    'class' => 'bg-success',
                ],
                'inactive' => [
                    'url' => "?id=$id&status={$arrayStatus['A']}",
                    'title' => Translator::trans('activate'),
                    'class' => 'bg-danger',
                ],
                'default' => [
                    'url' => "?id=$id&status={$arrayStatus['A']}",
                    'title' => Translator::trans('activate'),
                    'class' => 'bg-warning text-dark',
                ],
            ];

            $state = $links[$status] ?? $links['default'];

            return '<a href="' . $state['url'] . '" title="' . $state['title'] . '" data-bs-toggle="tooltip" data-bs-placement="top"><span class="badge ' . $state['class'] .'">' . Translator::trans($status) . '</span></a>';
        }

        return '<span class="badge bg-secondary">' . Translator::trans('unknown') . '</span>';
    }

    private static function renderImageCell(array $record, array $options = [], ?string $field = null): string
    {
        $field = $field ?? 'image';
        $src = htmlspecialchars($record[$field] ?? '');

        $srcDir = rtrim($options['src_dir'] ?? '', '/') . '/';
        $fullSrc = $src ? $srcDir . $src : $srcDir . 'placeholder.png';

        $altField = $options['alt_field'] ?? null;
        $alt = $src
            ? ($altField && isset($record[$altField]) ? htmlspecialchars($record[$altField]) : '')
            : Translator::trans('empty');

        $width = (int)($options['width'] ?? 20);

        $attTitle = "<img src='{$fullSrc}' class='img-fluid' alt='{$alt}'>";

        $html  = PHP_EOL . '<p class="m-0" data-bs-toggle="tooltip" data-bs-html="true" title="' . $attTitle . '">' . PHP_EOL;
        $html .= '    <img src="' . $fullSrc . '" class="img-fluid" alt="' . $alt . '" style="height:' . $width . 'px;">' . PHP_EOL;
        $html .= '</p>' . PHP_EOL;

        return $html . '    ';
    }
}
