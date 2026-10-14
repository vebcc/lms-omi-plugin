<?php

declare(strict_types=1);

namespace Omi\Config;

/**
 * Źródło surowych wartości konfiguracji (w LMS: tabela uiconfig / lms.ini).
 */
interface ConfigSource
{
    /**
     * Zwraca surową wartość ustawienia albo null, gdy nie jest ustawione (lub jest puste).
     */
    public function getValue(string $name): ?string;

    public function getBool(string $name, bool $default): bool;
}
