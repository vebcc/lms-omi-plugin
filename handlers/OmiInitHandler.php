<?php


class OmiInitHandler
{
    /**
     * Sets plugin Smarty modules directory
     *
     * @param array $hook_data Hook data
     * @return array Hook data
     */
    public function modulesDirInit(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSOmiPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }
}