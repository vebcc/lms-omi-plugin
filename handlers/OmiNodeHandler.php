<?php


class OmiNodeHandler
{
    public function nodeInfoBeforeDisplay(array $hook_data)
    {
        $this->assignOmiData($hook_data['smarty']);

        return $hook_data;
    }

    private function assignOmiData(Smarty $SMARTY)
    {
        $params = "";
        $omi = LMSOmiPlugin::getOmiInstance();
        $isAutomaticLoginEnabled = ConfigHelper::getConfig('phpui.olt_manager_automatic_login', false);
        if($isAutomaticLoginEnabled === "true"){
            $isAutomaticLoginEnabled = true;
        }else{
            $isAutomaticLoginEnabled = false;
        }

        if($isAutomaticLoginEnabled){
            $params.= '?x-auth-token='.ConfigHelper::getConfig('phpui.olt_manager_token');
            $params.= '&x-auth-additional-token='.$omi->getMyToken();

        }

        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('phpui.olt_manager_url'));
        $SMARTY->assign('omioltmanagertoken', ConfigHelper::getConfig('phpui.olt_manager_token'));
        $SMARTY->assign('omioltmanagerdevice', 'device');
        $SMARTY->assign('omioltmanagerparams', $params);


    }
}