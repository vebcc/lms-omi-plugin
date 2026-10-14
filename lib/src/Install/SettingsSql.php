<?php

declare(strict_types=1);

namespace Omi\Install;

use Omi\Config\OmiConfig;

/**
 * Generuje schemat instalacyjny pluginu (doc/lms.pgsql, doc/lms.mysql): dopisuje brakujące
 * ustawienia sekcji "omi" do uiconfig (nigdy nie nadpisuje istniejących) i zapisuje wersję
 * schematu w dbinfo. Pliki generuje bin/generate-install-sql.php.
 */
final class SettingsSql
{
    public const DIALECT_POSTGRES = 'postgres';
    public const DIALECT_MYSQL = 'mysql';

    public static function installSchema(string $dialect, string $dbVersion): string
    {
        $lines = [
            '-- LMSOmiPlugin - schemat instalacyjny (wygenerowany przez bin/generate-install-sql.php, nie edytuj ręcznie)',
            '-- Dodaje brakujące ustawienia sekcji omi, istniejących wartości nie zmienia.',
            '',
        ];

        foreach (OmiConfig::DEFINITIONS as $var => $definition) {
            $lines[] = self::insertIfMissing($dialect, OmiConfig::SECTION, $var, $definition['default'], $definition['description']);
        }

        $lines[] = '';
        $lines[] = sprintf(
            "INSERT INTO dbinfo (keytype, keyvalue) VALUES (%s, %s);",
            self::quote('dbversion_LMSOmiPlugin'),
            self::quote($dbVersion)
        );

        return implode("\n", $lines) . "\n";
    }

    public static function insertIfMissing(string $dialect, string $section, string $var, string $value, string $description): string
    {
        $from = $dialect === self::DIALECT_MYSQL ? ' FROM DUAL' : '';

        return sprintf(
            'INSERT INTO uiconfig (section, var, value, description, disabled) SELECT %s, %s, %s, %s, 0%s'
            . ' WHERE NOT EXISTS (SELECT 1 FROM uiconfig WHERE section = %s AND var = %s);',
            self::quote($section),
            self::quote($var),
            self::quote($value),
            self::quote($description),
            $from,
            self::quote($section),
            self::quote($var)
        );
    }

    /**
     * Literał SQL. Wartości pochodzą wyłącznie z OmiConfig::DEFINITIONS; znaki, które
     * parser LMSDB (MultiExecute) traktuje specjalnie, są tam zabronione (pilnuje test).
     */
    public static function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
