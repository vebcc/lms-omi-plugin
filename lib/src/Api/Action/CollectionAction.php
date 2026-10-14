<?php

declare(strict_types=1);

namespace Omi\Api\Action;

use Omi\Api\Action;
use Omi\Collection\CollectionProvider;
use Omi\Collection\CollectionSnapshot;

/**
 * Akcja kolekcji: {checksum, items} albo samo {checksum} przy checksumOnly=1.
 *
 * Suma kolekcji obejmuje sumy per-element, więc checksumOnly liczy to samo co pełne
 * pobranie - oszczędza transfer i serializację odpowiedzi.
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
        $snapshot = CollectionSnapshot::fromRaw($this->provider->fetchRaw());

        if (self::isChecksumOnly($params)) {
            return ['checksum' => $snapshot->checksum()];
        }

        return ['checksum' => $snapshot->checksum(), 'items' => $snapshot->items()];
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function isChecksumOnly(array $params): bool
    {
        $value = $params['checksumOnly'] ?? null;

        return is_string($value) && in_array(strtolower($value), ['1', 'true', 'yes'], true);
    }
}
