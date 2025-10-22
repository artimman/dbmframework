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

use Lib\DataTables\Src\Classes\DataTableRenderer;
use Lib\DataTables\Src\Interfaces\ConfigDataTableInterface;
use RuntimeException;

class ApiDataTableResponseBuilder
{
    public function __construct(
        private DataTableRenderer $datatableRender,
        private ConfigDataTableInterface $config
    ) {
    }

    public function getResponse(array $records, array $sider): array|string
    {
        return match ($this->config::getMode()) {
            'PHP' => $this->renderPhp($records, $sider),
            'AJAX' => $this->renderAjax($records, $sider),
            'API' => $this->renderApi($records, $sider),
            default => throw new RuntimeException("Unsupported mode"),
        };
    }

    private function renderPhp(array $records, array $sider): array
    {
        return $this->datatableRender->renderDataTableJson(
            $records,
            $sider,
            $this->config
        );
    }

    private function renderAjax(array $records, array $sider): array
    {
        return $this->datatableRender->renderDataTableJson(
            $records,
            $sider,
            $this->config
        );
    }

    private function renderApi(array $records, array $sider): array
    {
        return $this->datatableRender->renderDataTableJsonApi(
            records: $records,
            sider: $sider,
            config: $this->config
        );
    }
}
