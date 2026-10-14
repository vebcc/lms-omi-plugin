<?php

/*
 * LMSOmiPlugin 3.0.0: rejestracja ustawień sekcji "omi" (tylko brakujących).
 * Wykonywane przez LMSDB::UpgradeDb() w kontekście obiektu bazy ($this).
 * Nowe instalacje dostają to samo z doc/lms.*sql.
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoload.php';

\Omi\Install\SettingsInstaller::upgrade($this, '2026101400');
