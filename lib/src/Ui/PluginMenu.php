<?php

declare(strict_types=1);

namespace Omi\Ui;

/**
 * Menu i uprawnienia pluginu.
 */
final class PluginMenu
{
    public const MENU_KEY = 'omi';

    /**
     * Definicje uprawnień: nazwa => [etykieta, regexp modułów, pozycje menu].
     * Regexp jest dopasowywany przez LMS jako "/$regexp/i" do nazwy modułu.
     *
     * @return array<string, array{label: string, regexp: string, menu: array<string, mixed>|int|null}>
     */
    public static function permissions(bool $deviceErrorListEnabled, int $menuAll = 1): array
    {
        $permissions = [
            'omi_full_access' => [
                'label' => 'OltManager - full access',
                'regexp' => '^omi.*$',
                'menu' => [self::MENU_KEY => $menuAll],
            ],
            'omi_read_only' => [
                'label' => 'OltManager - information review',
                'regexp' => '^(omiapiproxy|omiredirect)$',
                'menu' => [self::MENU_KEY => ['omioltmanagerurl', 'omioltmanagerunauth']],
            ],
            'omi_api_user' => [
                'label' => 'OltManager - LMS API full access',
                'regexp' => '^omiapi$',
                'menu' => null,
            ],
        ];

        if ($deviceErrorListEnabled) {
            $permissions['omi_device_error_list'] = [
                'label' => 'OltManager - device error list access',
                'regexp' => '^omideviceerrorlist$',
                'menu' => [self::MENU_KEY => ['omideviceerrorlist']],
            ];
        }

        return $permissions;
    }

    /**
     * @param callable(string): string $trans
     * @return array<string, mixed>
     */
    public static function menu(bool $deviceErrorListEnabled, callable $trans): array
    {
        $submenu = [
            'omioltmanagerurl' => [
                'name' => $trans('OltManager'),
                'link' => '?m=omiredirect',
                'tip' => $trans('OltManager'),
                'prio' => 10,
            ],
            'omioltmanagerunauth' => [
                'name' => $trans('Unauthorized Onus'),
                'link' => '?m=omiredirect&url=tools/onu/unauthenticated/finder',
                'tip' => $trans('Unauthorized Onus'),
                'prio' => 10,
            ],
        ];

        if ($deviceErrorListEnabled) {
            $submenu['omideviceerrorlist'] = [
                'name' => $trans('Device with error list'),
                'link' => '?m=omideviceerrorlist',
                'tip' => $trans('Device with error list'),
                'prio' => 20,
            ];
        }

        return [
            self::MENU_KEY => [
                'name' => 'OltManager',
                'img' => 'LMSOmiPlugin/olt_manager.ico',
                'link' => '?m=omiredirect',
                'tip' => $trans('OltManager integration'),
                'accesskey' => 'k',
                'prio' => 40,
                'submenu' => $submenu,
            ],
        ];
    }

    /**
     * Wstawia menu pluginu przed sekcją "netdevices" (albo na końcu).
     *
     * @param array<string, mixed> $menu
     * @param array<string, mixed> $pluginMenu
     * @return array<string, mixed>
     */
    public static function insert(array $menu, array $pluginMenu): array
    {
        $position = array_search('netdevices', array_keys($menu), true);
        if ($position === false) {
            return $menu + $pluginMenu;
        }

        return array_slice($menu, 0, $position, true) + $pluginMenu + array_slice($menu, $position, null, true);
    }
}
