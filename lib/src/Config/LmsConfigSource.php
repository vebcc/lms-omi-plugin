<?php

declare(strict_types=1);

namespace Omi\Config;

/**
 * Odczyt ustawień z LMS (ConfigHelper).
 */
final class LmsConfigSource implements ConfigSource
{
    public function getValue(string $name): ?string
    {
        $value = \ConfigHelper::getConfig($name, null);
        if ($value === null || $value === false || is_array($value)) {
            return null;
        }
        $value = (string) $value;

        return $value === '' ? null : $value;
    }

    public function getBool(string $name, bool $default): bool
    {
        // checkConfig() zwraca null dla niepoprawnej wartości - wtedy domyślna
        $value = \ConfigHelper::checkConfig($name, $default);

        return is_bool($value) ? $value : $default;
    }
}
