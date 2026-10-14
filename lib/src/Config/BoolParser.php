<?php

declare(strict_types=1);

namespace Omi\Config;

/**
 * Interpretacja wartości logicznych zgodna z ConfigHelper::checkValue() z LMS.
 */
final class BoolParser
{
    /**
     * @param mixed $value
     */
    public static function parse($value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }
        if ($value === null || $value === '') {
            return $default;
        }

        $value = trim((string) $value);
        if (preg_match('/^(1|y|on|yes|true|tak|t|enabled)$/i', $value)) {
            return true;
        }
        if (preg_match('/^(0|n|no|off|false|nie|disabled)$/i', $value)) {
            return false;
        }

        return $default;
    }
}
