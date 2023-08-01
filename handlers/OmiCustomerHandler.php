<?php

class OmiCustomerHandler
{
    public function customerInfoBeforeDisplay(array $hook_data)
    {
        //$this->assignOmiData($hook_data['smarty']);

        return $hook_data;
    }


/*    public function customerInfoBeforeDisplay(array $hook_data)
    {
        global $LMS;

        $omi = LMSOmiPlugin::getOmiInstance();

        $SMARTY = $hook_data['smarty'];
        $resource_tabs = $SMARTY->getTemplateVars('resource_tabs');
        if (!isset($resource_tabs['omi-onu-list-box']) || $resource_tabs['omi-onu-list-box']) {
            require_once(PLUGINS_DIR . '/' . LMSOmiPlugin::PLUGIN_DIRECTORY_NAME . '/modules/omiontlistbox.inc.php');
//here dac assignomidata
        }

        return $hook_data;
    }*/

   /* private function assignOmiData(Smarty $SMARTY)
    {
        $params = "";
        $omi = LMSOmiPlugin::getOmiInstance();
        $isAutomaticLoginEnabled = ConfigHelper::getConfig('omi.olt_manager_automatic_login', false);
        if($isAutomaticLoginEnabled === "true"){
            $isAutomaticLoginEnabled = true;
        }else{
            $isAutomaticLoginEnabled = false;
        }

        if($isAutomaticLoginEnabled){
            $params.= '?x-auth-token='.ConfigHelper::getConfig('omi.olt_manager_token');
            $params.= '&x-auth-additional-token='.$omi->getMyToken();

        }

        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('omi.olt_manager_url'));
        $SMARTY->assign('omioltmanagertoken', ConfigHelper::getConfig('omi.olt_manager_token'));
        $SMARTY->assign('omioltmanagerparams', $params);
    }*/
}