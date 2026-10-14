<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Config\OmiConfig;
use Omi\Install\SettingsSql;
use PHPUnit\Framework\TestCase;

final class SettingsSqlTest extends TestCase
{
    private static function dbVersion(): string
    {
        preg_match("/PLUGIN_DB_VERSION\\s*=\\s*'(\\d{10})'/", (string) file_get_contents(dirname(__DIR__, 2) . '/LMSOmiPlugin.php'), $m);

        return $m[1] ?? '';
    }

    public function testInstallFilesAreUpToDate(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertSame(
            SettingsSql::installSchema(SettingsSql::DIALECT_POSTGRES, self::dbVersion()),
            file_get_contents($root . '/doc/lms.pgsql'),
            'Uruchom: php bin/generate-install-sql.php'
        );
        self::assertSame(
            SettingsSql::installSchema(SettingsSql::DIALECT_MYSQL, self::dbVersion()),
            file_get_contents($root . '/doc/lms.mysql'),
            'Uruchom: php bin/generate-install-sql.php'
        );
    }

    public function testUpgradeFilesExistForCurrentVersion(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertFileExists($root . '/lib/upgradedb/postgres.' . self::dbVersion() . '.php');
        self::assertFileExists($root . '/lib/upgradedb/mysql.' . self::dbVersion() . '.php');
    }

    public function testValuesAreSafeForLmsMultiExecute(): void
    {
        // MultiExecute w LMS dzieli zapytania po ";" i traktuje "|" i "\" specjalnie
        foreach (OmiConfig::DEFINITIONS as $name => $definition) {
            foreach ([$name, $definition['default'], $definition['description']] as $value) {
                self::assertDoesNotMatchRegularExpression("/[;'|\\\\]/", $value, $name);
            }
        }
    }

    public function testInsertNeverOverwrites(): void
    {
        $sql = SettingsSql::insertIfMissing(SettingsSql::DIALECT_POSTGRES, 'omi', 'ssl_verify', 'true', 'opis');

        self::assertStringStartsWith('INSERT INTO uiconfig', $sql);
        self::assertStringContainsString("WHERE NOT EXISTS (SELECT 1 FROM uiconfig WHERE section = 'omi' AND var = 'ssl_verify')", $sql);
        self::assertStringNotContainsString('UPDATE', $sql);
        self::assertStringContainsString('FROM DUAL', SettingsSql::insertIfMissing(SettingsSql::DIALECT_MYSQL, 'omi', 'a', 'b', 'c'));
    }
}
