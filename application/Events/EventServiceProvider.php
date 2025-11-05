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

namespace Dbm\Events;

class EventServiceProvider
{
    public function register(EventDispatcher $dispatcher): void
    {
        $mappings = include __DIR__ . '/../events.php';

        foreach ($mappings as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                $dispatcher->listen($event, new $listener());
            }
        }
    }
}
