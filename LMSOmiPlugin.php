<?php

/**
 * LMSOmiPlugin - integracja LMS z systemem OltManager.
 *
 * @author Krzysztof Masłowski <krzysztof@maslowski.it>
 *
 * Wymaga PHP 7.4+ (kod musi się parsować na PHP 7.4 - 8.5: plugin jest ładowany przy każdym
 * żądaniu LMS, błąd składni wyłącza całego LMS-a).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'autoload.php';

class LMSOmiPlugin extends LMSPlugin
{
    public const PLUGIN_DIRECTORY_NAME = 'LMSOmiPlugin';
    public const PLUGIN_NAME = 'LMS Olt Manager Integration Plugin';
    public const PLUGIN_ALIAS = 'olt-manager';
    public const PLUGIN_VERSION = '3.0.0';
    public const PLUGIN_SOFTWARE_VERSION = self::PLUGIN_VERSION;
    public const PLUGIN_DB_VERSION = '2026101400';
    public const PLUGIN_DESCRIPTION = 'Integracja z systemem OltManager (API v3, SSO)';
    public const PLUGIN_AUTHOR = 'Krzysztof Masłowski &lt;krzysztof@maslowski.it&gt;';
    public const PLUGIN_DOC_URL = 'https://docs.oltmanager.pl/books/oltmanager-dokumentacja/page/konfiguracja-integracji-lmsilms';
    public const PLUGIN_REPO_URL = 'https://github.com/vebcc/lms-omi-plugin';

    /** @var \Omi\ServiceContainer|null */
    private static $services = null;

    /**
     * Kontener usług pluginu (tworzony leniwie, raz na żądanie).
     */
    public static function services(): \Omi\ServiceContainer
    {
        if (self::$services === null) {
            self::$services = new \Omi\ServiceContainer(
                self::PLUGIN_VERSION,
                new \Omi\Config\LmsConfigSource(),
                new \Omi\Lms\LmsDatabase(LMSDB::getInstance()),
                new \Omi\Lms\LmsFacade(LMS::getInstance()),
                new \Omi\Security\LmsSessionStore(),
                static function (): ?int {
                    $id = class_exists('Auth') ? Auth::GetCurrentUser() : null;

                    return $id ? (int) $id : null;
                }
            );
        }

        return self::$services;
    }

    public function registerHandlers()
    {
        $this->handlers = [
            'smarty_initialized' => [
                'class' => 'OmiInitHandler',
                'method' => 'smartyInit',
            ],
            'modules_dir_initialized' => [
                'class' => 'OmiInitHandler',
                'method' => 'modulesDirInit',
            ],
            'menu_initialized' => [
                'class' => 'OmiInitHandler',
                'method' => 'menuInit',
            ],
            'access_table_initialized' => [
                'class' => 'OmiInitHandler',
                'method' => 'accessTableInit',
            ],
            'nodeinfo_before_display' => [
                'class' => 'OmiNodeHandler',
                'method' => 'nodeInfoBeforeDisplay',
            ],
            'netdevinfo_before_display' => [
                'class' => 'OmiNetDevHandler',
                'method' => 'netDevInfoBeforeDisplay',
            ],
            'customerinfo_before_display' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerInfoBeforeDisplay',
            ],
            'nodeedit_after_submit' => [
                'class' => 'OmiNodeHandler',
                'method' => 'nodeEditAfterSubmit',
            ],
            'nodeadd_after_submit' => [
                'class' => 'OmiNodeHandler',
                'method' => 'nodeAddAfterSubmit',
            ],
            'customeredit_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerEditAfterSubmit',
            ],
            'customeradd_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerAddAfterSubmit',
            ],
            'customerassignmentadd_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerAssignmentAddAfterSubmit',
            ],
            'customerassignmentedit_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerAssignmentEditAfterSubmit',
            ],
            'rtticketview_before_display' => [
                'class' => 'OmiTicketHandler',
                'method' => 'ticketViewBeforeDisplay',
            ],
        ];
    }
}
