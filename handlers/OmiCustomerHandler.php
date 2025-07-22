<?php

class OmiCustomerHandler
{
    public function customerInfoBeforeDisplay(array $hook_data)
    {
        $SMARTY = $hook_data['smarty'];
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

        return $hook_data;
    }

    public function customerEditAfterSubmit(array $hook_data)
    {
        if(!ConfigHelper::getConfig('omi.olt_manager_force_sync', false)){
            return $hook_data;
        }

        $this->runRequest('customer');

        return $hook_data;

    }

    public function customerAddAfterSubmit(array $hook_data)
    {
        if(!ConfigHelper::getConfig('omi.olt_manager_force_sync', false)){
            return $hook_data;
        }

        $this->runRequest('customer');

        return $hook_data;

    }

    public function customerAssignmentAddAfterSubmit(array $hook_data)
    {
        if(!ConfigHelper::getConfig('omi.olt_manager_force_sync', false)){
            return $hook_data;
        }

        $this->runRequest('customer');

        return $hook_data;

    }

    public function customerAssignmentEditAfterSubmit(array $hook_data)
    {
        if(!ConfigHelper::getConfig('omi.olt_manager_force_sync', false)){
            return $hook_data;
        }

        $this->runRequest('customer');

        return $hook_data;

    }

    private function runRequest($type, $timeout = 5)
    {
        $curl = curl_init();

        $url = ConfigHelper::getConfig('omi.olt_manager_url') . '/api/v1/integration/sync';
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'type=' . $type;

        $headers = [
            'X-AUTH-TOKEN: ' . ConfigHelper::getConfig('omi.olt_manager_token')
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FAILONERROR => true,
        ]);

        try {
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                error_log('Curl error: ' . curl_error($curl));
                $response = null;
            }
        } catch (Exception $e) {
            error_log('Exception during HTTP request: ' . $e->getMessage());
            $response = null;
        } finally {
            curl_close($curl);
        }

        return $response;
    }

}