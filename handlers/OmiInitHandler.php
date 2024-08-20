<?php


class OmiInitHandler
{
    /**
     * Sets plugin Smarty templates directory
     *
     * @param Smarty $hook_data Hook data
     * @return \Smarty Hook data
     */
    public function smartyInit(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSOmiPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);

        $SMARTY = $hook_data;

        return $hook_data;
    }

    /**
     * Sets plugin Smarty modules directory
     *
     * @param array $hook_data Hook data
     * @return array Hook data
     */
    public function modulesDirInit(array $hook_data = [])
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSOmiPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }

    /**
     * Sets plugin menu entries
     *
     * @param array $hook_data Hook data
     * @return array Hook data
     */
    public function menuInit(array $hook_data = [])
    {
        $menu_omi = [
            'omi' => [
                'name' => 'OltManager',
                'img' => 'LMSOmiPlugin/olt_manager.ico',
                'link' => ConfigHelper::getConfig('phpui.olt_manager_url', '?m=omideviceerrorlist'),
                'tip' => trans('OltManager integration'),
                'accesskey' =>'k',
                'prio' => 40,
                'submenu' => [
                    'omioltmanagerurl' => [
                        'name' => trans('OltManager'),
                        'link' => ConfigHelper::getConfig('phpui.olt_manager_url', '?m=omideviceerrorlist'),
                        'tip' => trans('OltManager'),
                        'prio' => 10,
                    ],
                    'omideviceerrorlist' => [
                        'name' => trans('Device with error list'),
                        'link' => '?m=omideviceerrorlist',
                        'tip' => trans('Device with error list'),
                        'prio' => 20,
                    ],
                ],
            ],
        ];

        $menu_keys = array_keys($hook_data);
        $i = array_search('netdevices', $menu_keys);
        return array_slice($hook_data, 0, $i, true) + $menu_omi + array_slice($hook_data, $i, null, true);
    }

    /**
     * Modifies access table
     *
     */
    public function accessTableInit()
    {
        $access = AccessRights::getInstance();

        $permission = new Permission(
            'omi_full_access',
            trans('OltManager - full access'),
            '^omi.*$',
            null,
            [
                'omi' => Permission::MENU_ALL
            ]
        );
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'omi_read_only',
            trans('OltManager - information review'),
            '^((omideviceerror)(info|list|search))$',
            null,
            [
                'omi' => [
                    'omideviceerrorlist',
                ]
            ]
        );
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'omi_api_data_getter',
            trans('OltManager - LMS API full access'),
            '^((omiapidata)(getter))$',
            null,
            [
                'omi' => [
                    'omiapidatagetter',
                ]
            ]
        );
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'omi_data_getter',
            trans('OltManager - OMI API full access'),
            '^((omidata)(getter))$',
            null,
            [
                'omi' => [
                    'omidatagetter',
                ]
            ]
        );
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

    }
}