<?php

declare(strict_types=1);

namespace Omi\Collection;

/**
 * Kolekcja w formacie API v3: lista elementów z sumami per-element + suma całości.
 * Suma jest liczona zawsze tą samą funkcją - niezależnie od tego, czy odpowiedź zawiera items.
 */
final class CollectionSnapshot
{
    /** @var list<array<mixed>> */
    private array $items;
    private string $checksum;

    /**
     * @param list<array<mixed>> $items
     */
    private function __construct(array $items, string $checksum)
    {
        $this->items = $items;
        $this->checksum = $checksum;
    }

    /**
     * @param array<mixed> $raw dane z CollectionProvider::fetchRaw()
     */
    public static function fromRaw(array $raw): self
    {
        $items = Checksum::items(Checksum::withItemChecksums($raw));

        return new self($items, Checksum::of($items));
    }

    /**
     * @return list<array<mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }
}
