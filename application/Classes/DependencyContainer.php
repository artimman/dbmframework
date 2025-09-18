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
 * Dependency Injection BETA. Used during testing.
 * Methods available in DI can be edited in application/services.php
 */

declare(strict_types=1);

namespace Dbm\Classes;

use Exception;
use ReflectionClass;

class DependencyContainer
{
    private array $services = [];
    private array $instances = [];

    public function set(string $name, callable $service): void
    {
        $this->services[$name] = $service;
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    public function get(string $name)
    {
        if (!isset($this->instances[$name])) {
            if (!isset($this->services[$name])) {
                if (class_exists($name)) {
                    $reflection = new ReflectionClass($name);
                    $constructor = $reflection->getConstructor();

                    if (!$constructor) {
                        $this->instances[$name] = new $name();
                    } else {
                        $dependencies = [];

                        foreach ($constructor->getParameters() as $param) {
                            $type = $param->getType();
                            if ($type && !$type->isBuiltin()) {
                                $dependencies[] = $this->get($type->getName());
                            } else {
                                throw new Exception("Cannot resolve dependency {$param->getName()} for service {$name}");
                            }
                        }

                        $this->instances[$name] = $reflection->newInstanceArgs($dependencies);
                    }
                } else {
                    throw new Exception("Service {$name} not found in container");
                }
            } else {
                $this->instances[$name] = $this->services[$name]($this);
            }
        }

        return $this->instances[$name];
    }
}
