<?php
/**
 * Library: DbM DataTables PHP
 * A class designed for the DbM Framework and for use in any PHP application.
 * Used template: Bootstrap 5
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * INFO! Docelowo przebudować bibliotekę z trzech na dwa tryby PHP (HTML) i API (JSON).
 * Dodać gotowe komponenty JS (np. w Vanilla/Vue/React), które komunikują się z backendem.
 */

declare(strict_types=1);

namespace Lib\DataTables\Src\Classes;

use Lib\DataTables\Src\Classes\Http\NativeRequest;
use Lib\DataTables\Src\Interfaces\ConfigDataTableInterface;
use Lib\DataTables\Src\Interfaces\Http\RequestInterface;
use Lib\DataTables\Src\Utility\Translator;

class DataTableRenderer
{
    private ?RequestInterface $request = null;

    private const ARRAY_PER_PAGE_OPTIONS = [1, 10, 20, 50, 100]; // TEMP! 1 - usuń
    private const CELL_TEXT_LIMIT = 48;

    public function __construct(?RequestInterface $request = null)
    {
        $this->request = $request ?? new NativeRequest();
    }

    /**
     * Renderuje całą tabelę (thead + tbody)
     */
    public function renderDataTable(
        array $records,
        array $sider,
        ConfigDataTableInterface $config,
        ?string $mode = null,
        ?string $url = null
    ): string {
        $mode = $config::getMode($mode);
        $url = $config::getUrl($url);

        // --- FREE version restriction
        /* if ($mode !== 'PHP') {
            return $this->renderAvailableInPRO($mode);
        } */

        $schema  = $config->getTableConfig();
        $filters = $config->getFilters();
        $buttons = $config->getButtons();

        $html = PHP_EOL . '<div class="datatableContainer" data-dt-url="' . $url . '" data-dt-mode="' . $mode . '">' . PHP_EOL;
        $html .= $this->renderDtHeader($sider, $filters, $buttons, $mode);
        $html .= '    <div class="table-responsive my-2 datatable-body">' . PHP_EOL;
        $html .= '        <table class="table table-striped my-0">' . PHP_EOL;
        $html .= '        <thead id="dtHead" class="text-nowrap">' . PHP_EOL;
        $html .= '            ' . $this->renderTheadColumns($sider, $schema);
        $html .= '        </thead>' . PHP_EOL;
        $html .= '        <tbody id="dtBody">' . PHP_EOL;
        $html .= '            ' . $this->renderTbodyRows($records, $schema, $sider, $mode, $config);
        $html .= '        </tbody>' . PHP_EOL;
        $html .= '        </table>' . PHP_EOL;
        $html .= '    </div>' . PHP_EOL;
        $html .= $this->renderDtFooter($sider);
        $html .= '</div>' . PHP_EOL;

        return $html;
    }

    /**
     * Render nagłówka powyżej tabeli
     */
    public function renderDtHeader(array $sider, array $filters = [], array $buttons = [], ?string $mode = null): string
    {
        $html = '    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mt-1 mt-md-3 datatable-header">' . PHP_EOL;
        $html .= '        ' . $this->renderPerPageSelect($sider, $mode) . PHP_EOL;
        $html .= '        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">' . PHP_EOL;
        $html .= '            ' . $this->renderFilters($filters, $mode);
        $html .= '            ' . $this->renderSearch();
        $html .= '            ' . $this->renderButtons($buttons);
        $html .= '        </div>' . PHP_EOL;
        $html .= '    </div>' . PHP_EOL;

        return $html;
    }

    /**
     * Render stopki poniżej tabeli
     */
    public function renderDtFooter(array $sider): string
    {
        $html = '    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start 
        align-items-md-center mb-1 mb-md-3 datatable-footer">' . PHP_EOL;
        $html .= '        <div id="dtInfo" class="align-self-start datatable-info">' . PHP_EOL;
        $html .= '            ' . $this->renderInfo($sider);
        $html .= '        </div>' . PHP_EOL;
        $html .= '        <nav id="dtPagination" class="mt-2 mt-md-0 datatable-pagination" aria-label="' . Translator::trans('pagination') . '">' . PHP_EOL;
        $html .= '            ' . $this->renderPagination($sider);
        $html .= '        </nav>' . PHP_EOL;
        $html .= '    </div>' . PHP_EOL;

        return $html;
    }

    /**
     * Renderuje nagłówek tabeli <thead>
     *
     * @param array $sider
     * @param array $schema
     */
    public function renderTheadColumns(array $sider, array $schema): string
    {
        $html = PHP_EOL . '<tr>' . PHP_EOL;

        foreach ($schema as $col) {
            if (!empty($col['hidden'])) {
                continue; // pomijamy kolumnę w nagłówku
            }

            $isVirtual = !empty($col['virtual']);
            $name = $col['name'] ?? $col['field'];
            $label = $col['label'] ?? ucfirst($name);
            $sortable = $isVirtual ? false : ($col['sortable'] ?? false);
            isset($col['class']) ? $hClass = ' class="' . $col['class'] . '"' : $hClass = '';

            $content = $sortable
                ? $this->renderThLink($name, $label, $sider)
                : htmlspecialchars($label);

            $html .= sprintf('    <th scope="col"%s>%s</th>' . PHP_EOL, $hClass, $content);
        }

        $html .= '</tr>' . PHP_EOL;

        return $html;
    }

    /**
     * Renderuje wiersze <tbody> z obsługą customowych wstawek po wskazanych pozycjach.
     *
     * @param array<int,array<string,mixed>|object> $records
     * @param array<int,array<string,mixed>> $schema
     * @param array|null $sider
     * @param string|null $mode
     * @param ConfigDataTableInterface|null $comfig
     */
    public function renderTbodyRows(
        array $records,
        array $schema,
        array $sider = [],
        ?string $mode = null,
        ?ConfigDataTableInterface $config = null
    ): string {
        $html = PHP_EOL;

        if (count($records) === 0) {
            $html .= '<tr><td colspan="' . max(1, count($schema)) . '" class="text-center small">'
                . Translator::trans('no_results')
                . '</td></tr>' . PHP_EOL;

            return $html;
        }

        // pobierz custom rows z configu
        $customRows = $config
            ? $config::getCustomRows($records, $schema)
            : [];

        // zmiana kluczy na pozycje, tablica w formacie [position => [rows...]]
        $indexed = [];
        foreach ($customRows as $cfg) {
            $pos = $cfg['position'] ?? 0;
            $indexed[$pos][] = $cfg;
        }

        // Insert any before rows (pos 0)
        if (!empty($indexed[0])) {
            foreach ($indexed[0] as $cfg) {
                $html .= $this->renderCustomRow($cfg, $schema);
            }
        }

        foreach ($records as $i => $record) {
            $html .= $this->renderOneRow($record, $schema, $i, $sider, $mode);

            if (!empty($indexed[$i + 1])) {
                foreach ($indexed[$i + 1] as $cfg) {
                    $html .= $this->renderCustomRow($cfg, $schema);
                }
            }
        }

        // after rows (po wszystkich danych)
        $count = count($records);
        foreach ($indexed as $pos => $cfgs) {
            if ($pos > $count) {
                foreach ($cfgs as $cfg) {
                    $html .= $this->renderCustomRow($cfg, $schema);
                }
            }
        }

        return $html;
    }

    /**
     * Renderuje dane tabeli w formacie JSON (AJAX - można usunąć, zmienić na dwa warianty HTML i JSON)
     */
    public function renderDataTableJson(
        array $records,
        array $sider,
        ConfigDataTableInterface $config
    ): array {
        $mode = $config::getMode();
        $schema = $config->getTableConfig();
        $customRows = $config::getCustomRows($records, $schema);

        return [
            'success' => true,
            'sider' => $sider,
            'thead_html' => $this->renderTheadColumns($sider, $schema) ?? '',
            'rows_html' => $this->renderTbodyRows($records, $schema, $sider, $mode, $config, $customRows) ?? '',
            'info_html' => $this->renderInfo($sider) ?? '',
            'pagination_html' => $this->renderPagination($sider) ?? '',
        ];
    }

    /**
     * Renderuje dane tabeli w formacie JSON -> API
     */
    public function renderDataTableJsonApi(
        array $records,
        array $sider,
        ConfigDataTableInterface $config
    ): array {
        $tableConfig = $config->getTableConfig();

        // Mapowanie kolumn do wyświetlenia (bez hidden)
        $columns = array_values(array_filter(array_map(function ($col) {
            $isVirtual = !empty($col['virtual']);

            return [
                'field' => $col['field'] ?? null,
                'label' => $col['label'] ?? '',
                'sortable' => $isVirtual ? false : ($col['sortable'] ?? false),
                'class' => $col['class'] ?? null,
                'tag' => $col['tag'] ?? null,
                'tag_options' => $col['tag_options'] ?? null,
                'hidden' => $col['hidden'] ?? false,
                'virtual' => $col['virtual'] ?? false,
            ];
        }, $tableConfig), fn ($c) => empty($c['hidden'])));

        // Mapowanie rekordów – WAŻNE: hidden też dodajemy do rows!
        $rows = [];
        foreach ($records as $i => $row) {
            $item = [];
            foreach ($tableConfig as $col) {
                $field = $col['field'] ?? null;
                if (!$field) {
                    continue;
                }

                if (!empty($col['virtual'])) {
                    if (isset($col['formatter']) && is_callable($col['formatter'])) {
                        $lp = $i + 1 + ($sider['offset'] ?? 0);
                        $item[$field] = $col['formatter']($row, $lp);
                    }
                    continue;
                }

                if (isset($col['formatter']) && is_callable($col['formatter'])) {
                    $lp = $i + 1 + ($sider['offset'] ?? 0);
                    $item[$field] = $col['formatter']($row, $lp);
                } else {
                    $item[$field] = $row[$field] ?? null;
                }
            }
            $rows[] = $item;
        }

        // Pobieramy customowe wiersze z configu
        $customRows = $config::getCustomRows($records, $tableConfig);

        return [
            "success"    => true,
            "sider"      => $sider,
            "columns"    => $columns,
            "rows"       => $rows,
            "filters"    => $config->getFilters(),
            "buttons"    => $config->getButtons(),
            "customRows" => $customRows,
        ];
    }

    /**
     * Renderuje pojedynczy wiersz tabeli
     *
     * @param array|object $record
     * @param array<int,array<string,mixed>> $schema
     * @param int $index
     * @param array|null $sider
     * @param string|null $mode
     */
    private function renderOneRow(array|object $record, array $schema, int $index = 0, array $sider = [], ?string $mode = null): string
    {
        $html = '<tr>' . PHP_EOL;

        foreach ($schema as $col) {
            if (!empty($col['hidden'])) {
                continue;
            }

            $field = $col['field'] ?? ($col['name'] ?? '');
            $hClass = isset($col['class']) ? ' class="' . $col['class'] . '"' : '';
            $formatter = $col['formatter'] ?? null;
            $tag = $col['tag'] ?? null;
            $isVirtual = !empty($col['virtual']);

            if ($tag && CellRenderer::specialCellSupports($tag)) {
                $value = CellRenderer::renderSpecialCell($col, $record);
            } elseif (is_callable($formatter)) {
                if ($isVirtual || array_key_exists($field, $record)) {
                    $lp = $index + 1 + ($sider['offset'] ?? 0);
                    $value = $formatter($record, $lp);

                    if ($value === null) {
                        $value = $this->renderEmpty();
                    }
                } else {
                    $value = $this->renderEmpty();
                }
            } else {
                if (!$isVirtual) {
                    $val = $record[$field] ?? null;
                    if ($val !== null && $val !== '') {
                        if ($tag || (is_string($val) && str_contains($val, '<'))) {
                            $value = $val;
                        } else {
                            $value = RenderHelper::safeTruncate((string) $val, self::CELL_TEXT_LIMIT);
                        }
                    } else {
                        $value = $this->renderEmpty();
                    }
                } else {
                    $value = $this->renderEmpty();
                }
            }

            $html .= sprintf('    <td%s>%s</td>' . PHP_EOL, $hClass, $value);
        }

        $html .= '</tr>' . PHP_EOL;

        return $html;
    }

    private function renderThLink(string $name, string $label, array $sider): string
    {
        // Aktualna kolumna i kierunek
        $currentColumn = $sider['sort'] ?? 'id';
        $currentDir = strtoupper($sider['dir'] ?? 'ASC');

        // Styl koloru
        $color = ($currentColumn === $name) ? 'text-dark' : 'text-black-50';

        // Ikony kierunku
        $icons = [
            'ASC'  => 'bi bi-arrow-up-square',
            'DESC' => 'bi bi-arrow-down-square',
        ];

        $arrow = ($currentColumn === $name) ? ($icons[$currentDir] ?? $icons['ASC']) : 'bi bi-arrow-down-up';

        // Nowy kierunek (toggle)
        $dir = ($currentColumn === $name && $currentDir === 'ASC') ? 'DESC' : 'ASC';

        // Parametry URL
        $params = $this->request->getQueryParams();
        unset($params['sort'], $params['dir'], $params['page']); // usuwamy stare sortowanie i paginację
        $params['sort'] = $name;
        $params['dir']  = $dir;
        $params['page'] = 1; // reset strony po zmianie sortowania

        $link = '?' . http_build_query($params);

        // Etykieta z &nbsp; dla spójnego wyrównania
        $anchor = str_replace(' ', '&nbsp;', ucfirst($label));

        return sprintf(
            '<a href="%s" class="text-decoration-none d-block link-dark">%s <i class="%s %s ms-1"></i></a>',
            htmlspecialchars($link),
            $anchor,
            $arrow,
            $color
        );
    }

    /**
     * @param array $sider
     * @param $mode mode
     * @param array $options = [10,20,50,100] default for renderPerPageSelect()
     */
    private function renderPerPageSelect(array $sider, ?string $mode = null, array $options = self::ARRAY_PER_PAGE_OPTIONS): string
    {
        $html = PHP_EOL . '<div class="d-inline-flex align-items-center datatable-per-page mb-2 mb-md-0">' . PHP_EOL;
        $html .= '    <select id="dtPerPage" class="form-select form-select-sm w-auto"';
        if (strtoupper($mode) === 'PHP') {
            $html .= ' onchange="location.href=this.value"';
        }
        $html .= '>' . PHP_EOL;

        foreach ($options as $option) {
            $params = array_merge($this->request->getQueryParams(), ['per_page' => $option, 'page' => 1]);
            $selected = ($option == $sider['perPage']) ? ' selected' : '';

            $html .= sprintf(
                '        <option value="?%s"%s>%d</option>' . PHP_EOL,
                http_build_query($params),
                $selected,
                $option
            );
        }

        $html .= '    </select>' . PHP_EOL;
        $html .= '    <span class="small ms-2">' . Translator::trans('entries_page') . '</span>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;

        return $html;
    }

    private function renderFilters(array $filters, ?string $mode = null): ?string
    {
        if (empty($filters)) {
            return null;
        }

        // Current values
        $vals = [];
        foreach ($filters as $name => $_) {
            $vals[$name] = (string)($this->request->getQuery('filter_' . $name) ?? '');
        }

        // Preserve GET (bez filter_* i page)
        $preserve = $this->request->getQueryParams();
        foreach (array_keys($filters) as $name) {
            unset($preserve['filter_' . $name]);
        }

        unset($preserve['page']);

        // Helper: budowa query bez pustych
        $buildQuery = static function (array $params): string {
            $parts = [];
            foreach ($params as $k => $v) {
                if ($v === '' || $v === null) {
                    continue;
                }

                if (is_array($v)) {
                    foreach ($v as $vi) {
                        if ($vi === '' || $vi === null) {
                            continue;
                        }
                        $parts[] = rawurlencode($k) . '[]=' . rawurlencode((string)$vi);
                    }

                    continue;
                }

                $parts[] = rawurlencode($k) . '=' . rawurlencode((string)$v);
            }

            return implode('&', $parts);
        };

        // Action: sama ścieżka, bez query
        $actionPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: ($_SERVER['PHP_SELF'] ?? '/');

        // Formularz + JS
        $html = PHP_EOL . '<div class="datatable-filters">' . PHP_EOL;
        $html .= '    <form action="' . htmlspecialchars($actionPath) . '" method="get" id="dtFilters" class="input-group input-group-sm flex-nowrap"';
        if (strtoupper($mode) === 'PHP') {
            $html .= ' onsubmit="(function(f){Array.from(f.querySelectorAll(\'select[name^=filter_]\')).forEach(function(s){if(!s.value){s.removeAttribute(\'name\');}});return true;})(this)"';
        }
        $html .= '>' . PHP_EOL;

        // Hidden tylko dla NIE-filter_* parametrów
        foreach ($preserve as $k => $v) {
            if (strpos($k, 'filter_') === 0) {
                continue;
            }
            if ($v === '' || $v === null) {
                continue;
            }

            if (is_array($v)) {
                foreach ($v as $vi) {
                    if ($vi === '' || $vi === null) {
                        continue;
                    }
                    $html .= sprintf(
                        '        <input type="hidden" name="%s[]" value="%s">' . PHP_EOL,
                        htmlspecialchars($k),
                        htmlspecialchars((string)$vi)
                    );
                }
            } else {
                $html .= sprintf(
                    '        <input type="hidden" name="%s" value="%s">' . PHP_EOL,
                    htmlspecialchars($k),
                    htmlspecialchars((string)$v)
                );
            }
        }

        // Selecty filter_<name>
        $i = 1;
        foreach ($filters as $name => $data) {
            $label = $data['label'] ?? ucfirst($name);
            $options = $data['options'] ?? [];

            $html .= sprintf('        <select id="dtFilters_' . $i . '" class="form-select" name="%s">' . PHP_EOL, htmlspecialchars('filter_' . $name));
            $html .= sprintf('            <option value="">- %s -</option>' . PHP_EOL, htmlspecialchars($label));

            foreach ($options as $opt) {
                $val = (string)($opt['value'] ?? '');
                $lab = (string)($opt['label'] ?? $val);
                $sel = ($val === $vals[$name]) ? ' selected' : '';
                $html .= sprintf(
                    '            <option value="%s"%s>%s</option>' . PHP_EOL,
                    htmlspecialchars($val),
                    $sel,
                    htmlspecialchars($lab)
                );
            }

            $html .= '        </select>' . PHP_EOL;
            $i++;
        }

        // Submit
        $html .= '        <button type="submit" class="btn btn-sm btn-outline-primary" title="' . Translator::trans('filter') . '">'
            . '<i class="bi bi-funnel"></i></button>' . PHP_EOL;

        // Reset (tylko non-filter params)
        $cleanPreserve = [];

        foreach ($preserve as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            if (strpos($k, 'filter_') === 0) {
                continue;
            }
            $cleanPreserve[$k] = $v;
        }
        $resetQuery = $buildQuery($cleanPreserve);
        $resetUrl = $actionPath . ($resetQuery ? '?' . $resetQuery : '');

        $html .= sprintf(
            '        <a href="%s" id="dtResetFilters" class="btn btn-sm btn-outline-secondary" title="' . Translator::trans('reset_filters') . '">'
            . '<i class="bi bi-x-circle"></i></a>' . PHP_EOL,
            htmlspecialchars($resetUrl)
        );

        $html .= '    </form>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;

        return $html;
    }

    private function renderSearch(): string
    {
        // Obsługa parametru wyszukiwania: priorytet ma "q", alias "query"
        $value = $this->request->getQuery('q') ?? $this->request->getQuery('query') ?? '';
        $params = $this->request->getQueryParams();
        unset($params['q'], $params['query'], $params['page']);

        $queryParams = http_build_query($params);

        $html = PHP_EOL . '<div class="datatable-search">' . PHP_EOL;
        $html .= '    <form method="GET">' . PHP_EOL;
        $html .= '        <div class="input-group">' . PHP_EOL;
        $html .= '            <input type="hidden" name="%s" value="%s">' . PHP_EOL;
        $html .= '            <input type="text" name="q" value="%s" id="dtSearch" class="form-control form-control-sm" placeholder="%s">' . PHP_EOL;
        $html .= '            <button type="submit" class="btn btn-sm btn-outline-primary" title="%s"><i class="bi bi-search"></i></button>' . PHP_EOL;
        $html .= '        </div>' . PHP_EOL;
        $html .= '    </form>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;

        return sprintf(
            $html,
            $queryParams ? key($params) : '',
            $queryParams ? reset($params) : '',
            htmlspecialchars($value, ENT_QUOTES),
            Translator::trans('search_placeholder'),
            Translator::trans('search'),
        );
    }

    /**
     * Example:
     *  $buttons = [['label' => 'Eksport CSV',
     *   'url' => '#export-csv',
     *   'target' = '_blank',
     *   'icon' => 'bi bi-filetype-csv',
     *   'class' => 'btn btn-sm btn-outline-success'
     * ]];
     */
    private function renderButtons(array $buttons): ?string
    {
        if (empty($buttons)) {
            return null;
        }

        $html =  PHP_EOL . '<div id="dtButtons" class="btn-group datatable-buttons">' . PHP_EOL;

        foreach ($buttons as $btn) {
            $label = $btn['label'] ?? Translator::trans('action');
            $url = $btn['url'] ?? '#';
            $target = $btn['target'] ?? '_self';
            $icon = $btn['icon'] ?? 'bi bi-file';
            $class = $btn['class'] ?? 'btn-outline-primary';

            $html .= sprintf(
                '    <a href="%s" target="%s" class="btn btn-sm %s" title="%s">%s</a>' . PHP_EOL,
                $url,
                $target,
                $class,
                $label,
                $icon ? '<i class="' . $icon . '"></i>' : ''
            );
        }

        $html .= '</div>' . PHP_EOL;

        return $html;
    }

    private function renderInfo(array $sider): ?string
    {
        $from = ($sider['page'] - 1) * $sider['perPage'] + 1;
        $to = min($sider['total'], $sider['page'] * $sider['perPage']);

        if ($to > 0) {
            return '<span class="small">' . Translator::trans('records_info', $from, $to, $sider['total']) . '</span>' . PHP_EOL;
        }

        return null;
    }

    /**
     * Renderuje paginację (bootstrapowa) na podstawie danych z $sider.
     */
    private function renderPagination(array $sider): ?string
    {
        $current = (int) ($sider['page'] ?? 1);
        $perPage = (int) ($sider['perPage'] ?? 20);
        $total = (int) ($sider['total'] ?? 0);
        $pages = (int) ceil($total / $perPage);

        if ($pages <= 1) {
            return '<!-- Pagination -->' . PHP_EOL;
        }

        $buttons = $this->paginationButtons($current, $pages, 2);

        $html = PHP_EOL . '    <ul class="pagination pagination-sm m-0">' . PHP_EOL;

        // Prev
        $prevDisabled = ($current <= 1) ? ' disabled' : '';
        $html .= sprintf(
            '        <li class="page-item%s"><a class="page-link" href="%s" aria-label="' . Translator::trans('previous') .'">&lsaquo;</a></li>' . PHP_EOL,
            $prevDisabled,
            $current <= 1 ? '#' : $this->buildPageUrl($current - 1)
        );

        // Buttons
        foreach ($buttons as $b) {
            if ($b === '...') {
                $html .= '        <li class="page-item disabled"><span class="page-link">...</span></li>' . PHP_EOL;
                continue;
            }

            $active = ($b === $current) ? ' active' : '';
            $activeLink = ($b === $current) ? ' bg-secondary link-light border border-dark' : ' link-dark';
            $aria = ($b === $current) ? ' aria-current="page"' : '';

            $html .= sprintf(
                '        <li class="page-item%s"><a class="page-link%s" href="%s"%s>%d</a></li>' . PHP_EOL,
                $active,
                $activeLink,
                htmlspecialchars($this->buildPageUrl($b), ENT_QUOTES),
                $aria,
                $b
            );
        }

        // Next
        $nextDisabled = ($current >= $pages) ? ' disabled' : '';
        $html .= sprintf(
            '        <li class="page-item%s"><a class="page-link" href="%s" aria-label="' . Translator::trans('next') . '">&rsaquo;</a></li>' . PHP_EOL,
            $nextDisabled,
            $current >= $pages ? '#' : $this->buildPageUrl($current + 1)
        );

        $html .= '    </ul>' . PHP_EOL;

        return $html;
    }

    /**
     * Renderuje wiersz niestandardowy
     */
    private function renderCustomRow(array $config, array $schema): string
    {
        $tag = $config['_tag'] ?? null;

        switch ($tag) {
            case 'notice_row':
                $colspan = $config['colspan'] ?? count($schema);
                $msg = htmlspecialchars($config['message'] ?? Translator::trans('row_no_message'));

                $html = '<tr class="table-info">' . PHP_EOL;
                $html .= '    <td colspan="' . $colspan . '" class="text-center">' . $msg . '</td>' . PHP_EOL;
                $html .= '</tr>' . PHP_EOL;

                return $html;
            case 'custom_html':
                $colspan = $config['colspan'] ?? count($schema);

                $html = '<tr>' . PHP_EOL;
                $html .= '    <td colspan="' . $colspan . '">' . ($config['html'] ?? '') . '</td>' . PHP_EOL;
                $html .= '</tr>' . PHP_EOL;

                return $html;
            case 'sum_row':
                // TODO! More options... colspan (6:4:3)
                $colspan = $config['colspan'] ?? count($schema);
                $sum = $config['sum'] ?? 0;

                $html = '<tr class="table-warning">' . PHP_EOL;
                $html .= '   <td colspan="6" class="text-end"><strong>' . Translator::trans('row_total') . '</strong></td>' . PHP_EOL;
                $html .= '   <td colspan="5"><strong>' . $sum . '</strong></td>' . PHP_EOL;
                $html .= '</tr>' . PHP_EOL;

                return $html;
            default:
                return '';
        }
    }

    /**
     * Pagination helper - Generuje listę przycisków (numery + elipsy).
     */
    private function paginationButtons(int $current, int $total, int $adjacents = 2): array
    {
        $buttons = [];

        if ($total <= 1) {
            return $buttons;
        }

        $buttons[] = 1;

        $start = max(2, $current - $adjacents);
        $end   = min($total - 1, $current + $adjacents);

        if ($start > 2) {
            $buttons[] = '...';
        }

        for ($i = $start; $i <= $end; $i++) {
            $buttons[] = $i;
        }

        if ($end < $total - 1) {
            $buttons[] = '...';
        }

        if ($total > 1) {
            $buttons[] = $total;
        }

        // Usuń duplikaty liczb (ale zachowaj wszystkie '...'):
        $final = [];
        $seenNums = [];
        foreach ($buttons as $b) {
            if ($b === '...') {
                $final[] = $b;
                continue;
            }

            $num = (int) $b;
            if (in_array($num, $seenNums, true)) {
                continue; // pomiń powtórkę numeru strony
            }
            $seenNums[] = $num;
            $final[] = $num;
        }

        return $final;
    }

    /* --- Helpers --- */

    /**
     * Buduje URL do danej strony.
     */
    private function buildPageUrl(int $page, string $basePath = '', array $extra = []): string
    {
        $uri = $this->request->getServerParams()['REQUEST_URI'];
        $params = $this->request->getQueryParams();

        $base = $basePath !== '' ? $basePath : strtok($uri, '?');
        $params = array_merge($params, $extra, ['page' => $page]);

        return $base . '?' . http_build_query($params);
    }

    private function renderEmpty(): string
    {
        return '<span class="badge bg-secondary">' . Translator::trans('empty') . '</span>';
    }

    private function renderAvailableInPRO(string $feature): string
    {
        $html  = '<div class="alert alert-warning text-center my-3">';
        $html .= 'The <strong>' . htmlspecialchars($feature) . '</strong> version of the library is available in <a href="https://dbm.org.pl/" class="link-dark fw-bold" target="_blank">DbM DataTables PRO</a>.';
        $html .= '</div>';

        return $html;
    }
}
