<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * Usage:
 * ------------
 * // 1. Register events in application/events.php:
 * return [
 *     UserLoggedIn::class => [LogUserLogin::class],
 *     UserRegistered::class => [SendWelcomeEmail::class],
 * ];
 *
 * // 2. Dispatch events in code:
 * $dispatcher = new EventDispatcher();
 * $provider = new EventServiceProvider();
 * $provider->register($dispatcher);
 *
 * // Fire immediately
 * $dispatcher->dispatch(new UserLoggedIn($userId, $email));
 *
 * // Add to async queue
 * $dispatcher->dispatchAsync(new UserRegistered($userId, $email));
 *
 * // Process queue (manually or by worker)
 * $dispatcher->processQueue();
 *
 * Implementation notes:
 * ---------------------
 * - dispatch() executes listeners immediately (sync)
 * - dispatchAsync() stores serialized events in /data/events/dbm_event_queue.json
 * - processQueue() reads and clears the queue
 * - The queue directory is auto-created and validated for write access
 *
 * Recommended usage:
 * ------------------
 * - Use sync events for in-process logic (logging, metrics)
 * - Use async events for background tasks (email sending, notifications)
 * - Run processQueue() from CRON or a long-running worker
 *
 * - - - EXAMPLE Event & Listener - - -
 *
 * namespace App\Events;
 *
 * class UserLoggedIn
 * {
 *     public function __construct(public int $userId, public string $email) {}
 * }
 *
 * - - -
 * namespace App\Listeners;
 *
 * class LogUserLogin implements ListenerInterface
 * {
 *     public function handle(object $event): void
 *     {
 *         if ($event instanceof UserLoggedIn) {
 *             $logger = new Logger();
 *             $logger->info("User {$event->email} logged in (ID {$event->userId})");
 *         }
 *     }
 * }
 */

declare(strict_types=1);

namespace Dbm\Events;

use Dbm\Events\Queue\EventQueue;

class EventDispatcher
{
    protected array $listeners = [];
    protected EventQueue $queue;

    public function __construct(?EventQueue $queue = null)
    {
        $this->queue = $queue ?? new EventQueue();
    }

    /**
     * Dodaje listener do zdarzenia
     */
    public function listen(string $event, callable|object $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * [SYNC] Natychmiast wywołuje listener (nic nie zapisuje)
     */
    public function dispatch(object $event): void
    {
        $eventClass = get_class($event);
        foreach ($this->listeners[$eventClass] ?? [] as $listener) {
            if (is_callable($listener)) {
                $listener($event);
            } elseif (method_exists($listener, 'handle')) {
                $listener->handle($event);
            }
        }
    }

    /**
     * [ASYNC] Dodaje event do kolejki (zapisuje do JSON)
     */
    public function dispatchAsync(object $event): void
    {
        $this->queue->push($event);
    }

    /**
     * [WORKER] Czyta plik, wykonuje eventy, a następnie czyści plik
     */
    public function processQueue(): void
    {
        foreach ($this->queue->pullAll() as $event) {
            $this->dispatch($event);
        }
    }
}
