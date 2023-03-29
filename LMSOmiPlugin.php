<?php


/**
 * LMSOltManagerIntegrationPlugin
 *
 * @author Krzysztof Masłowski <krzysztof@maslowski.it>
 *
 * PHP 7.1
 */

class LMSOmiPlugin extends LMSPlugin
{
    const PLUGIN_DIRECTORY_NAME = 'LMSOmiPlugin';
    const PLUGIN_NAME = 'LMS Olt Manager Integration Plugin';
    const PLUGIN_DESCRIPTION = 'Integracja z systemem OltManager';
    const PLUGIN_AUTHOR = 'Krzysztof Masłowski &lt;krzysztof@maslowski.it&gt;';

    const PLUGIN_MODE = 'DEV'; // DEV/PROD; SET TO PROD WHEN GOING TO PRODUCTION!!!

    private static $omi = null;

    public static function getOmiInstance(): ?OMI
    {
        if (empty(self::$omi)) {
            self::$omi = new OMI();
        }
        return self::$omi;
    }

    public function registerHandlers()
    {
        $this->handlers = [
            'modules_dir_initialized' => [
                'class' => 'OmiInitHandler',
                'method' => 'modulesDirInit',
            ],
        ];
    }
}
