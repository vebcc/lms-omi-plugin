<?php

declare(strict_types=1);

namespace Omi\Handler;

use Omi\Ui\OnuPanelAssigner;

/**
 * Wspólna logika handlerów kart LMS z panelem ONU i wymuszaniem synchronizacji.
 */
trait PanelHandlerTrait
{
    /**
     * @param array<string, mixed> $hook_data
     */
    private function isOnuTabHidden(array $hook_data): bool
    {
        $resource_tabs = $hook_data['smarty']->getTemplateVars('resource_tabs');

        return isset($resource_tabs['olt-manager-onu']) && !$resource_tabs['olt-manager-onu'];
    }

    /**
     * @param \Smarty $smarty
     */
    private function assignOnuPanel($smarty, string $section, string $title, string $extraLinkParams = ''): void
    {
        try {
            $services = \LMSOmiPlugin::services();
            $assigner = new OnuPanelAssigner($services->config(), $services->csrf(), self::canRunOnuActions());
            $assigner->assign($smarty, $section, $title, $extraLinkParams);
        } catch (\Throwable $e) {
            error_log('[LMSOmiPlugin] ONU panel: ' . get_class($e) . ': ' . $e->getMessage());
        }
    }

    private function forceSync(string $type): void
    {
        try {
            $services = \LMSOmiPlugin::services();
            if (!$services->config()->forceSync()) {
                return;
            }
            $client = $services->oltManagerClient();
            if (!$client->isConfigured()) {
                return;
            }
            $result = $client->triggerSync($type);
            if (!$result->isSuccessful()) {
                $services->logger()->error(sprintf(
                    'Force sync (%s) failed: %s',
                    $type,
                    $result->isTransportOk() ? 'HTTP ' . $result->status() : $result->error()
                ));
            }
        } catch (\Throwable $e) {
            error_log('[LMSOmiPlugin] force sync: ' . get_class($e) . ': ' . $e->getMessage());
        }
    }

    private static function canRunOnuActions(): bool
    {
        global $LMS;

        $userId = \Auth::GetCurrentUser();
        if (!$userId || !isset($LMS)) {
            return false;
        }
        $rights = $LMS->GetUserRights($userId);

        return is_array($rights) && \AccessRights::getInstance()->checkRights('omiapiaction', $rights);
    }
}
