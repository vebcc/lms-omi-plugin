<?php

/**
 * Minimalny autoloader PSR-4 dla przestrzeni nazw Omi\ (lib/src).
 *
 * LMS ładuje klasy pluginów przez classmap composera (`composer dump-autoload` w katalogu LMS),
 * ale po aktualizacji pluginu classmap bywa nieaktualny - ten autoloader jest zabezpieczeniem,
 * dzięki któremu nowe klasy działają bez przebudowy classmapy.
 */

declare(strict_types=1);

if (!defined('OMI_PLUGIN_AUTOLOAD_REGISTERED')) {
    define('OMI_PLUGIN_AUTOLOAD_REGISTERED', true);

    spl_autoload_register(static function (string $class): void {
        if (strncmp($class, 'Omi\\', 4) !== 0) {
            return;
        }

        $file = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 4)) . '.php';

        if (is_file($file)) {
            require $file;
        }
    });
}
