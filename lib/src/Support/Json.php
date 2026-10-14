<?php

declare(strict_types=1);

namespace Omi\Support;

final class Json
{
    /**
     * Flagi kodowania odpowiedzi API v3.
     */
    public const RESPONSE_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Flagi kodowania na potrzeby sum kontrolnych - dla poprawnych danych wynik jest
     * identyczny jak json_encode() bez flag (zgodność sum per-element z pluginem 2.x).
     */
    public const CHECKSUM_FLAGS = JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * @param mixed $value
     */
    public static function encode($value, int $flags = self::RESPONSE_FLAGS): string
    {
        $json = json_encode($value, $flags);

        return $json === false ? 'null' : $json;
    }

    /**
     * @return mixed
     */
    public static function decode(string $json)
    {
        return json_decode($json, true);
    }
}
