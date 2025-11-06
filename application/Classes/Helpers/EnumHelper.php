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

use Dbm\Classes\Log\Logger;
use Throwable;

/**
 * Universal, cached enum helper with logging support.
 */
class EnumHelper
{
    private static array $enumCache = [];
    private Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger();
    }

    /**
     * Get all enum values in a safe and cached way.
     *
     * @param string $enumClass Fully qualified enum class name.
     * @return array<string, mixed> Enum name => value pairs.
     */
    public function getEnum(string $enumClass): array
    {
        if (isset(self::$enumCache[$enumClass])) {
            return self::$enumCache[$enumClass];
        }

        if (!class_exists($enumClass) || !enum_exists($enumClass)) {
            $this->logger->warning("Enum class '{$enumClass}' does not exist or is invalid.");
            return [];
        }

        try {
            $cases = $enumClass::cases();
        } catch (Throwable $e) {
            $this->logger->error(
                "Failed to read enum cases for '{$enumClass}': " . $e->getMessage(),
                ['exception' => $e]
            );
            return [];
        }

        $result = [];
        foreach ($cases as $case) {
            $result[$case->name] = $case->value;
        }

        return self::$enumCache[$enumClass] = $result;
    }

    /**
     * Get single enum value by name in a safe way.
     */
    public function getEnumValue(string $enumClass, string $caseName): mixed
    {
        $values = $this->getEnum($enumClass);
        return $values[$caseName] ?? null;
    }
}
