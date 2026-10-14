<?php

/**
 * Generuje doc/lms.pgsql i doc/lms.mysql z listy ustawień Omi\Config\OmiConfig::DEFINITIONS.
 * Użycie: php bin/generate-install-sql.php
 */

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use Omi\Install\SettingsSql;

$root = dirname(__DIR__);

if (!preg_match("/PLUGIN_DB_VERSION\\s*=\\s*'(\\d{10})'/", (string) file_get_contents($root . '/LMSOmiPlugin.php'), $matches)) {
    fwrite(STDERR, 'PLUGIN_DB_VERSION not found' . PHP_EOL);
    exit(1);
}
$version = $matches[1];

file_put_contents($root . '/doc/lms.pgsql', SettingsSql::installSchema(SettingsSql::DIALECT_POSTGRES, $version));
file_put_contents($root . '/doc/lms.mysql', SettingsSql::installSchema(SettingsSql::DIALECT_MYSQL, $version));

echo 'Wygenerowano doc/lms.pgsql i doc/lms.mysql dla wersji ' . $version . PHP_EOL;
