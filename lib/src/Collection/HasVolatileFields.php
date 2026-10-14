<?php

declare(strict_types=1);

namespace Omi\Collection;

/**
 * Kolekcja z polami zmieniającymi się bez zmiany danych (np. czas względny "x temu").
 * API v3 je pomija, żeby suma kontrolna zmieniała się tylko przy realnej zmianie danych.
 */
interface HasVolatileFields
{
    /**
     * @return list<string> klucze usuwane z elementów w API v3
     */
    public function volatileFields(): array;
}
