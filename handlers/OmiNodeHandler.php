<?php


class OmiNodeHandler
{
    public function nodeInfoBeforeDisplay(array $hook_data)
    {
        $SMARTY = $hook_data['smarty'];

        $resource_tabs = $SMARTY->getTemplateVars('resource_tabs');
        if (isset($resource_tabs['olt-manager-onu']) && !$resource_tabs['olt-manager-onu']) {
            return $hook_data;
        }

        $isOpenInNewTabEnabled = ConfigHelper::getConfig('omi.olt_manager_open_in_new_tab', false);
        if($isOpenInNewTabEnabled === "true" || $isOpenInNewTabEnabled === true || $isOpenInNewTabEnabled == 1){
            $isOpenInNewTabEnabled = true;
        }else{
            $isOpenInNewTabEnabled = false;
        }

        $onuLinkParams = ConfigHelper::getConfig('omi.olt_manager_onu_link_params', '?enabled=1');

        // Proxy endpoint – zapytania do OltManager API idą przez serwer LMS.
        // Token nigdy nie opuszcza serwera.
        $proxyUrl = '?m=omiapiproxy';

        $SMARTY->assign('omiproxyurl', $proxyUrl);
        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('omi.olt_manager_url'));
        $SMARTY->assign('omioltmanageronulinkparams', $onuLinkParams);
        $SMARTY->assign('omioltmanagersection', 'device');
        $SMARTY->assign('omioltmanagersectiontitle', 'OltManager device ONU list');
        $SMARTY->assign('omioltmanagernewtab', $isOpenInNewTabEnabled);

        return $hook_data;
    }

    public function nodeAddAfterSubmit(array $hook_data)
    {
        if(!ConfigHelper::getConfig('omi.olt_manager_force_sync', false)){
            return $hook_data;
        }

        $this->runRequest('device');

        return $hook_data;
    }

    public function nodeEditAfterSubmit(array $hook_data)
    {
        if(!ConfigHelper::getConfig('omi.olt_manager_force_sync', false)){
            return $hook_data;
        }

        $this->runRequest('device');

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