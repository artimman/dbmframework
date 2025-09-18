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

namespace App\Service;

class IndexService
{
    public function getMetaIndex(): array
    {
        return [
            'meta.title' => "Your Web Application Name",
            'meta.description' => "Web application description...",
            'meta.keywords' => "application keywords",
        ];
    }

    public function getMetaStart(): array
    {
        return [
            'meta.title' => "Welcome to DbM Framework!",
            'meta.description' => "Your lightweight and flexible framework for building powerful web applications.",
            'meta.keywords' => "high performance, easy configuration, comprehensive documentation",
            'meta.robots' => "noindex,nofollow",
        ];
    }

    public function getMetaInstaller(): array
    {
        return [
            'meta.title' => "DbM Framework Installer",
            'meta.description' => "A tool for installing modules.",
            'meta.keywords' => "installer, modules, tool",
            'meta.robots' => "noindex,nofollow",
        ];
    }

    public function alertMessage(array $msg): array
    {
        $map = [
            'success' => 'messageSuccess',
            'error' => 'messageDanger',
            'info' => 'messageInfo',
            'warning' => 'messageWarning',
        ];

        $type = $msg['type'] ?? 'info';
        $message = $msg['message'] ?? '';

        $alertType = $map[$type] ?? 'messageInfo';

        return [
            'type' => $alertType,
            'message' => $message,
        ];
    }
}
