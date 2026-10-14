<?php

declare(strict_types=1);

namespace Omi\Install;

use Omi\Config\OmiConfig;

/**
 * Dopisuje brakujące ustawienia sekcji "omi" - używane w plikach lib/upgradedb.
 * Nigdy nie nadpisuje istniejących wartości.
 */
final class SettingsInstaller
{
    /**
     * @param \LMSDB_common $db w plikach upgradedb: $this
     */
    public static function upgrade($db, string $dbVersion): void
    {
        $db->BeginTrans();

        foreach (OmiConfig::DEFINITIONS as $var => $definition) {
            $exists = (int) $db->GetOne(
                'SELECT COUNT(*) FROM uiconfig WHERE section = ? AND var = ?',
                [OmiConfig::SECTION, $var]
            );
            if ($exists === 0) {
                $db->Execute(
                    'INSERT INTO uiconfig (section, var, value, description, disabled) VALUES (?, ?, ?, ?, 0)',
                    [OmiConfig::SECTION, $var, $definition['default'], $definition['description']]
                );
            }
        }

        $db->Execute('UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?', [$dbVersion, 'dbversion_LMSOmiPlugin']);

        $db->CommitTrans();
    }
}
