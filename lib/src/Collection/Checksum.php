<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Support\Json;

/**
 * Sumy kontrolne kolekcji. Suma elementu = md5(json_encode(element)) - tak samo jak
 * w pluginie 2.x, więc sumy per-element zapisane wcześniej przez OltManager pozostają ważne.
 */
final class Checksum
{
    /**
     * @param mixed $value
     */
    public static function of($value): string
    {
        return md5(Json::encode($value, Json::CHECKSUM_FLAGS));
    }

    /**
     * Dodaje pole "checksum" do każdego elementu (tablicy); skalarne klucze meta zostają bez zmian.
     *
     * @param array<mixed> $raw
     * @return array<mixed>
     */
    public static function withItemChecksums(array $raw): array
    {
        foreach ($raw as $key => $item) {
            if (is_array($item)) {
                $raw[$key]['checksum'] = self::of($item);
            }
        }

        return $raw;
    }

    /**
     * Lista elementów (JSON array) bez kluczy meta LMS.
     *
     * @param array<mixed> $raw
     * @return list<array<mixed>>
     */
    public static function items(array $raw): array
    {
        $items = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
