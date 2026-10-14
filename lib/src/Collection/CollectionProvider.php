<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Api\ApiException;

/**
 * Źródło jednej kolekcji synchronizowanej z OltManagerem.
 */
interface CollectionProvider
{
    /**
     * Nazwa akcji API v3 (np. "customers").
     */
    public function name(): string;

    /**
     * Dane w kształcie legacy (tak jak zwracał je LMS / stary plugin): elementy bez sum
     * kontrolnych, z zachowaniem kluczy i ewentualnych skalarnych kluczy meta LMS
     * ("total", "order", ...).
     *
     * @return array<mixed>
     * @throws ApiException np. feature_disabled
     */
    public function fetchRaw(): array;
}
