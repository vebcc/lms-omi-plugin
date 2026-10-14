<?php

declare(strict_types=1);

namespace Omi\Api\Action;

use Omi\Api\Action;
use Omi\Collection\CollectionProvider;
use Omi\Collection\CollectionSnapshot;
use Omi\Collection\HasVolatileFields;

/**
 * Akcja kolekcji z warunkowym pobraniem (docs/API_V3.md, sekcja 3):
 * kolekcja jest budowana raz; gdy ifChecksum = bieżąca suma -> {checksum, notModified: true},
 * w przeciwnym razie -> {checksum, notModified: false, items}.
 */
final class CollectionAction implements Action
{
    private CollectionProvider $provider;

    public function __construct(CollectionProvider $provider)
    {
        $this->provider = $provider;
    }

    public function name(): string
    {
        return $this->provider->name();
    }

    public function handle(array $params)
    {
        $snapshot = CollectionSnapshot::fromRaw($this->withoutVolatileFields($this->provider->fetchRaw()));

        $checksum = $snapshot->checksum();

        if (self::ifChecksum($params) === $checksum) {
            return ['checksum' => $checksum, 'notModified' => true];
        }

        return ['checksum' => $checksum, 'notModified' => false, 'items' => $snapshot->items()];
    }

    /**
     * @param array<mixed> $raw
     *
     * @return array<mixed>
     */
    private function withoutVolatileFields(array $raw): array
    {
        if (!$this->provider instanceof HasVolatileFields) {
            return $raw;
        }

        $fields = array_flip($this->provider->volatileFields());
        foreach ($raw as $key => $item) {
            if (is_array($item)) {
                $raw[$key] = array_diff_key($item, $fields);
            }
        }

        return $raw;
    }

    /**
     * Suma kontrolna posiadana przez klienta (md5, małe litery) albo null.
     *
     * @param array<string, mixed> $params
     */
    private static function ifChecksum(array $params): ?string
    {
        $value = $params['ifChecksum'] ?? null;
        if (!is_string($value)) {
            return null;
        }
        $value = strtolower(trim($value));

        return preg_match('/^[0-9a-f]{32}$/', $value) === 1 ? $value : null;
    }
}
