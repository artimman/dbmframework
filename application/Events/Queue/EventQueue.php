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

namespace Dbm\Events\Queue;

class EventQueue
{
    protected string $file;

    public function __construct(?string $file = null)
    {
        $defaultPath = __DIR__ . '/../../../data/events';

        if (!is_dir($defaultPath)) {
            mkdir($defaultPath, 0777, true);
        }

        if (!is_writable($defaultPath)) {
            throw new \RuntimeException("Katalog {$defaultPath} nie ma uprawnień do zapisu.");
        }

        $this->file = $file ?? $defaultPath . '/dbm_event_queue.json';

        if (!file_exists($this->file)) {
            file_put_contents($this->file, json_encode([]));
        }
    }

    public function push(object $event): void
    {
        $queue = json_decode(file_get_contents($this->file), true);

        $queue[] = [
            'class' => get_class($event),
            'data' => serialize($event),
        ];

        file_put_contents($this->file, json_encode($queue));
    }

    public function pullAll(): array
    {
        $queue = json_decode(file_get_contents($this->file), true);
        file_put_contents($this->file, json_encode([]));

        return array_map(fn ($e) => unserialize($e['data']), $queue);
    }
}
