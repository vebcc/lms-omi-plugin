<?php

class OmiCustomerHandler
{
    public function customerInfoBeforeDisplay(array $hook_data)
    {
        $this->assignOmiData($hook_data['smarty']);

        return $hook_data;
    }

    private function assignOmiData(Smarty $SMARTY)
    {
        $params = "";
        $omi = LMSOmiPlugin::getOmiInstance();
        $isAutomaticLoginEnabled = ConfigHelper::getConfig('omi.olt_manager_automatic_login', false);
        if($isAutomaticLoginEnabled === "true" || $isAutomaticLoginEnabled === true || $isAutomaticLoginEnabled == 1){
            $isAutomaticLoginEnabled = true;
        }else{
            $isAutomaticLoginEnabled = false;
        }

        $isOpenInNewTabEnabled = ConfigHelper::getConfig('omi.olt_manager_open_in_new_tab', false);
        if($isOpenInNewTabEnabled === "true" || $isOpenInNewTabEnabled === true || $isOpenInNewTabEnabled == 1){
            $isOpenInNewTabEnabled = true;
        }else{
            $isOpenInNewTabEnabled = false;
        }

        if($isAutomaticLoginEnabled){
            $params.= '?x-auth-token='.ConfigHelper::getConfig('omi.olt_manager_token');
            $params.= '&x-auth-additional-token='.$omi->getMyToken();

        }

        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('omi.olt_manager_url'));
        $SMARTY->assign('omioltmanagertoken', ConfigHelper::getConfig('omi.olt_manager_token'));
        $SMARTY->assign('omioltmanageronulinkparams', ConfigHelper::getConfig('omi.olt_manager_onu_link_params', '?enabled=1'));
        $SMARTY->assign('omioltmanagersection', 'customer');
        $SMARTY->assign('omioltmanagersectiontitle', 'OltManager customer ONU list');
        $SMARTY->assign('omioltmanagernewtab', $isOpenInNewTabEnabled);
        $SMARTY->assign('omioltmanagerparams', $params);
    }
}