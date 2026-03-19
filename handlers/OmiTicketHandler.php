<?php

class OmiTicketHandler
{
    public function ticketViewBeforeDisplay(array $hook_data)
    {
        $SMARTY = $hook_data['smarty'];

        if(!isset($hook_data['ticket'])){
            return $hook_data;
        }

        $customerId = $hook_data['ticket']['customerid'];
        $nodeId = $hook_data['ticket']['nodeid'];

        if(!$customerId && !$nodeId){
            return $hook_data;
        }

        $params = '';
        if($customerId){
            $params = 'ownerLmsId=' . $customerId;
        }
        if($nodeId){
            if($params){
                $params .= '&';
            }
            $params .= 'deviceLmsId=' . $nodeId;
        }

        $isOpenInNewTabEnabled = ConfigHelper::getConfig('omi.olt_manager_open_in_new_tab', false);
        if($isOpenInNewTabEnabled === "true" || $isOpenInNewTabEnabled === true || $isOpenInNewTabEnabled == 1){
            $isOpenInNewTabEnabled = true;
        }else{
            $isOpenInNewTabEnabled = false;
        }

        $onuLinkParams = ConfigHelper::getConfig('omi.olt_manager_onu_link_params', '?enabled=1');

        if(strpos($onuLinkParams, '?') !== false){
            $onuLinkParams .= '&' . $params;
        }else{
            $onuLinkParams .= '?' . $params;
        }

        $proxyUrl = '?m=omiapiproxy';

        $SMARTY->assign('omiproxyurl', $proxyUrl);
        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('omi.olt_manager_url'));
        $SMARTY->assign('omioltmanageronulinkparams', $onuLinkParams);
        $SMARTY->assign('omioltmanagersection', 'ticket');
        $SMARTY->assign('omioltmanagersectiontitle', 'OltManager ticket ONU list');
        $SMARTY->assign('omioltmanagernewtab', $isOpenInNewTabEnabled);

        return $hook_data;
    }
}