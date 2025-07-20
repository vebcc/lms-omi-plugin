<?php

/**
 * LMSOmiPlugin
 *
 * @author Krzysztof Masłowski <krzysztof@maslowski.it>
 *
 * PHP 7.3
 */

class LMSOmiPlugin extends LMSPlugin
{
    const PLUGIN_DIRECTORY_NAME = 'LMSOmiPlugin';
    const PLUGIN_NAME = 'LMS Olt Manager Integration Plugin';
    const PLUGIN_DESCRIPTION = 'Integracja z systemem OltManager';
    const PLUGIN_AUTHOR = 'Krzysztof Masłowski &lt;krzysztof@maslowski.it&gt;';

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
                'method' => 'menuInit'
            ],
            'access_table_initialized' => [
                'class' => 'OmiInitHandler',
                'method' => 'accessTableInit'
            ],
            'nodeinfo_before_display' => [
                'class' => 'OmiNodeHandler',
                'method' => 'nodeInfoBeforeDisplay'
            ],
            'netdevinfo_before_display' => [
                'class' => 'OmiNetDevHandler',
                'method' => 'netDevInfoBeforeDisplay'
            ],
            'customerinfo_before_display' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerInfoBeforeDisplay'
            ],
            /////////////////////////////////////////
            'nodeedit_after_submit' => [
                'class' => 'OmiNodeHandler',
                'method' => 'nodeEditAfterSubmit'
            ],
            'nodeadd_after_submit' => [
                'class' => 'OmiNodeHandler',
                'method' => 'nodeAddAfterSubmit'
            ],
            'customeredit_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerEditAfterSubmit'
            ],
            'customeradd_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerAddAfterSubmit'
            ],
            'customerassignmentadd_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerAssignmentAddAfterSubmit'
            ],
            'customerassignmentedit_after_submit' => [
                'class' => 'OmiCustomerHandler',
                'method' => 'customerAssignmentEditAfterSubmit'
            ],
            /////////////////////////////////////
            'rtticketview_before_display' => [
                'class' => 'OmiTicketHandler',
                'method' => 'ticketViewBeforeDisplay'
            ],
        ];
    }
}
