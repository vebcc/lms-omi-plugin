<?php

use Omi\Ui\PluginMenu;

/**
 * Inicjalizacja pluginu: szablony, katalog modułów, menu i uprawnienia.
 */
class OmiInitHandler
{
    /**
     * @param Smarty $hook_data
     * @return Smarty
     */
    public function smartyInit($hook_data)
    {
        $template_dirs = (array) $hook_data->getTemplateDir();
        array_unshift($template_dirs, self::pluginDir() . DIRECTORY_SEPARATOR . 'templates');
        $hook_data->setTemplateDir($template_dirs);
        $hook_data->registerClass('LMSOmiPlugin', 'LMSOmiPlugin');

        return $hook_data;
    }

    /**
     * @param array<int, string> $hook_data
     * @return array<int, string>
     */
    public function modulesDirInit($hook_data = [])
    {
        array_unshift($hook_data, self::pluginDir() . DIRECTORY_SEPARATOR . 'modules');

        return $hook_data;
    }

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function menuInit($hook_data = [])
    {
        $enabled = LMSOmiPlugin::services()->config()->deviceErrorListEnabled();

        return PluginMenu::insert($hook_data, PluginMenu::menu($enabled, static function (string $text): string {
            return trans($text);
        }));
    }

    public function accessTableInit(): void
    {
        $access = AccessRights::getInstance();
        $enabled = LMSOmiPlugin::services()->config()->deviceErrorListEnabled();

        foreach (PluginMenu::permissions($enabled, Permission::MENU_ALL) as $name => $definition) {
            $access->insertPermission(
                new Permission($name, trans($definition['label']), $definition['regexp'], null, $definition['menu']),
                AccessRights::FIRST_FORBIDDEN_PERMISSION
            );
        }
    }

    private static function pluginDir(): string
    {
        return dirname(__DIR__);
    }
}
