<?php

declare(strict_types=1);

namespace Omi\Support;

/**
 * Odpowiedniki str_starts_with/str_contains dla PHP 7.4.
 */
final class Strings
{
    public static function startsWith(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    public static function contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }

    /**
     * Dokleja parametry zapytania do URL-a z właściwym separatorem.
     */
    public static function appendQuery(string $url, string $query): string
    {
        $query = ltrim($query, '?&');
        if ($query === '') {
            return $url;
        }

        return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
    }
}
