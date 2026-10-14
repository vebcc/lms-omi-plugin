<?php

declare(strict_types=1);

namespace Omi\Config;

/**
 * Konfiguracja w pamięci - do testów i narzędzi CLI.
 */
final class ArrayConfigSource implements ConfigSource
{
    /** @var array<string, mixed> */
    private array $values;

    /**
     * @param array<string, mixed> $values klucze w postaci "omi.nazwa"
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function getValue(string $name): ?string
    {
        if (!array_key_exists($name, $this->values) || $this->values[$name] === null) {
            return null;
        }
        $value = $this->values[$name];
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        $value = (string) $value;

        return $value === '' ? null : $value;
    }

    public function getBool(string $name, bool $default): bool
    {
        return BoolParser::parse($this->values[$name] ?? null, $default);
    }
}
