<?php

declare(strict_types=1);

namespace Omi\Proxy;

/**
 * Biała lista akcji ONU, które przeglądarka może zlecić przez moduł omiapiaction.
 * Odpowiada adresom generowanym przez OltManager w polu "functions" listy ONU
 * (route olt_manager_onu_run: /olt/manager/{olt}/onu/{onu}/run/{function}[/{format}[/{template}]]).
 */
final class OnuActionEndpoint
{
    private const PATTERN = '#^olt/manager/\d+/onu/\d+/run/[A-Za-z0-9]+(/[A-Za-z]+){0,2}$#';

    public static function normalize(string $raw): ?string
    {
        $path = RelativePath::normalize($raw);
        if ($path === null || !preg_match(self::PATTERN, $path)) {
            return null;
        }

        return $path;
    }
}
