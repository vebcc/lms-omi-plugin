<?php

declare(strict_types=1);

namespace Omi\Ui;

use Omi\Config\OmiConfig;
use Omi\Security\CsrfTokenManager;

/**
 * Zmienne Smarty dla panelu listy ONU (omiontlistbox.inc.html) - wspólne dla
 * karty klienta, komputera, urządzenia sieciowego i zgłoszenia.
 */
final class OnuPanelAssigner
{
    public const PROXY_URL = '?m=omiapiproxy';
    public const ACTION_URL = '?m=omiapiaction';

    private OmiConfig $config;
    private CsrfTokenManager $csrf;
    private bool $canRunActions;

    public function __construct(OmiConfig $config, CsrfTokenManager $csrf, bool $canRunActions)
    {
        $this->config = $config;
        $this->csrf = $csrf;
        $this->canRunActions = $canRunActions;
    }

    /**
     * @param \Smarty $smarty
     * @param string $extraLinkParams dodatkowe parametry filtrowania listy ONU (np. "ownerLmsId=1")
     */
    public function assign($smarty, string $section, string $title, string $extraLinkParams = ''): void
    {
        $smarty->assign('omiproxyurl', self::PROXY_URL);
        $smarty->assign('omiactionurl', self::ACTION_URL);
        $smarty->assign('omicsrftoken', $this->canRunActions ? $this->csrf->token() : '');
        $smarty->assign('omicanrunactions', $this->canRunActions);
        $smarty->assign('omioltmanagerurl', $this->config->oltManagerUrl());
        $smarty->assign('omioltmanageronulinkparams', self::linkParams($this->config->onuLinkParams(), $extraLinkParams));
        $smarty->assign('omioltmanagersection', $section);
        $smarty->assign('omioltmanagersectiontitle', $title);
        $smarty->assign('omioltmanagernewtab', $this->config->openInNewTab());
        $smarty->assign('omioltmanagerautologin', $this->config->automaticLogin());
    }

    /**
     * Parametry filtrowania listy ONU, zawsze w postaci "?a=b&c=d" (sam "?" gdy brak).
     */
    public static function linkParams(string $configured, string $extra = ''): string
    {
        $parts = array_filter(
            [ltrim(trim($configured), '?&'), ltrim(trim($extra), '?&')],
            static function (string $part): bool {
                return $part !== '';
            }
        );

        return '?' . implode('&', $parts);
    }
}
