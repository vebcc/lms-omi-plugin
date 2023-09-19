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

        $acsSupport = (bool)ConfigHelper::getConfig('omi.acs_view', false);

        $SMARTY->assign('acsSupport', $acsSupport);

        if($acsSupport){
            $omionuconfiguration = $omi->getPPPoECredentials(['nodeId' => $_GET['id']]);
            $omionuconfigurationlist = [];

            foreach ($omionuconfiguration['net'] as $key => $row){
                $settingKey = 'conf.net.' . $key;
                $omionuconfigurationlist[] = ['key' => $settingKey, 'value' => $row];
            }

            foreach ($omionuconfiguration['tv'] as $key => $row){
                $settingKey = 'conf.tv.' . $key;
                $omionuconfigurationlist[] = ['key' => $settingKey, 'value' => $row];
            }

            foreach ($omionuconfiguration['wifi'][2] as $key => $row){
                $settingKey = 'conf.wifi.2.' . $key;
                $omionuconfigurationlist[] = ['key' => $settingKey, 'value' => $row];
            }

            foreach ($omionuconfiguration['wifi'][5] as $key => $row){
                $settingKey = 'conf.wifi.5.' . $key;
                $omionuconfigurationlist[] = ['key' => $settingKey, 'value' => $row];
            }


            foreach ($omionuconfigurationlist as $key => $row){
                if(is_array($row['value'])){
                    $row['value'] = implode(',', $row['value']);
                    $omionuconfigurationlist[$key] = $row;
                }
            }

            $page = (!$_GET['page'] ? 1 : $_GET['page']);
            $pagelimit = ConfigHelper::getConfig('omi.onu_configuration_pagelimit', count($omionuconfigurationlist));
            $start = ($page - 1) * $pagelimit;

            $SMARTY->assign('omionuconfigurationlist', $omionuconfigurationlist);
            $SMARTY->assign('page', $page);
            $SMARTY->assign('pagelimit', $pagelimit);
            $SMARTY->assign('start', $start);
        }

        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('omi.olt_manager_url'));
        $SMARTY->assign('omioltmanagertoken', ConfigHelper::getConfig('omi.olt_manager_token'));
        $SMARTY->assign('omioltmanagerdevice', 'device');
        $SMARTY->assign('omioltmanagerparams', $params);


    }
}