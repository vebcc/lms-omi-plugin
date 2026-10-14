<?php

declare(strict_types=1);

namespace Omi\Proxy;

use Omi\Support\Strings;

/**
 * Walidacja ścieżek względnych przekazywanych z przeglądarki (proxy, redirect, SSO target).
 *
 * Ścieżka zawsze jest doklejana do skonfigurowanego adresu OltManagera, ale odrzucamy
 * wszystko, co mogłoby zmienić host albo wyjść poza aplikację: schemat, "//", "\",
 * segmenty "." i "..", znaki sterujące i fragment "#".
 */
final class RelativePath
{
    public const MAX_LENGTH = 2048;

    /**
     * Zwraca znormalizowaną ścieżkę bez wiodącego "/" (np. "api/v1/onu/?enabled=1")
     * albo null, gdy ścieżka jest niedozwolona lub pusta.
     */
    public static function normalize(string $raw): ?string
    {
        if ($raw === '' || strlen($raw) > self::MAX_LENGTH) {
            return null;
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $raw) || strpos($raw, '\\') !== false || strpos($raw, '#') !== false) {
            return null;
        }
        if (Strings::startsWith($raw, '//')) {
            return null;
        }

        $candidate = Strings::startsWith($raw, '/') ? (string) substr($raw, 1) : $raw;

        $queryPos = strpos($candidate, '?');
        $path = $queryPos === false ? $candidate : (string) substr($candidate, 0, $queryPos);

        if (!self::isSafePath($path)) {
            return null;
        }

        return $candidate === '' ? null : $candidate;
    }

    /**
     * Ścieżka docelowa dla SSO / przekierowań: zawsze zaczyna się od pojedynczego "/".
     * Niedozwolona lub pusta wartość daje "/".
     */
    public static function target(string $raw): string
    {
        $normalized = self::normalize($raw);

        return '/' . ($normalized ?? '');
    }

    private static function isSafePath(string $path): bool
    {
        if (Strings::startsWith($path, '/')) {
            return false;
        }
        // schemat (http:, javascript:, ...) - dwukropek w pierwszym segmencie
        $firstSegment = explode('/', $path, 2)[0];
        if (strpos($firstSegment, ':') !== false) {
            return false;
        }

        // sprawdzamy też postać zdekodowaną (np. %2e%2e, %5c, %2f%2f)
        $decoded = $path;
        for ($i = 0; $i < 3; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        foreach ([$path, $decoded] as $variant) {
            if (preg_match('/[\x00-\x1F\x7F]/', $variant) || strpos($variant, '\\') !== false) {
                return false;
            }
            foreach (explode('/', $variant) as $segment) {
                if ($segment === '.' || $segment === '..') {
                    return false;
                }
            }
        }
        if (Strings::startsWith($decoded, '/')) {
            return false;
        }

        return true;
    }
}
