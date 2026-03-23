<?php


class OmiNetDevHandler
{
    public function netDevInfoBeforeDisplay(array $hook_data)
    {
        $this->assignOmiData($hook_data['smarty']);

        return $hook_data;
    }

    private function assignOmiData(Smarty $SMARTY)
    {
        $isOpenInNewTabEnabled = ConfigHelper::getConfig('omi.olt_manager_open_in_new_tab', false);
        if($isOpenInNewTabEnabled === "true" || $isOpenInNewTabEnabled === true || $isOpenInNewTabEnabled == 1){
            $isOpenInNewTabEnabled = true;
        }else{
            $isOpenInNewTabEnabled = false;
        }

        $isAutomaticLoginEnabled = ConfigHelper::getConfig('omi.olt_manager_automatic_login', false);
        if($isAutomaticLoginEnabled === "true" || $isAutomaticLoginEnabled === true || $isAutomaticLoginEnabled == 1){
            $isAutomaticLoginEnabled = true;
        }else{
            $isAutomaticLoginEnabled = false;
        }

        $onuLinkParams = ConfigHelper::getConfig('omi.olt_manager_onu_link_params', '?enabled=1');

        // Proxy endpoint – zapytania do OltManager API idą przez serwer LMS.
        // Token nigdy nie opuszcza serwera.
        $proxyUrl = '?m=omiapiproxy';

        $SMARTY->assign('omiproxyurl', $proxyUrl);
        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('omi.olt_manager_url'));
        $SMARTY->assign('omioltmanageronulinkparams', $onuLinkParams);
        $SMARTY->assign('omioltmanagersection', 'networkdevice');
        $SMARTY->assign('omioltmanagersectiontitle', 'OltManager device ONU list');
        $SMARTY->assign('omioltmanagernewtab', $isOpenInNewTabEnabled);
        $SMARTY->assign('omioltmanagerautoligin', $isAutomaticLoginEnabled);
    }
}