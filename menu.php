<?php

$menu['LMSOMIPLUGIN'] = [
    'plug'	=> true,
    'name' => 'OltManager',
    'img' => 'LMSOmiPlugin/olt_manager.ico',
    'link' => '?m=omideviceerrorlist',
    /*'link' => ConfigHelper::getConfig('phpui.olt_manager_url', '?m=omideviceerrorlist'),*/
    'tip' => trans('OltManager integration'),
    'accesskey' => 'k',
    'prio' => 40,
    'submenu' => [
        /*'omioltmanagerurl' => [
            'name' => trans('OltManager'),
            'link' => ConfigHelper::getConfig('phpui.olt_manager_url', '?m=omideviceerrorlist'),
            'tip' => trans('OltManager'),
            'prio' => 10,
        ],*/
        'omideviceerrorlist' => [
            'name' => trans('Device with error list'),
            'link' => '?m=omideviceerrorlist',
            'tip' => trans('Device with error list'),
            'prio' => 20,
        ],
    ],
];

?>