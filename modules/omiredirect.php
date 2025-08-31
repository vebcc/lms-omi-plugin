<?php

$omi = LMSOmiPlugin::getOmiInstance();

$layout['pagetitle'] = 'OMI - API';

$params = $_GET;

$url = isset($params['url']) ? $params['url'] : '';

$outParams = '';

$omurl = ConfigHelper::getConfig('omi.olt_manager_url', 'https://oltmanager.pl');

$isAutomaticLoginEnabled = ConfigHelper::getConfig('omi.olt_manager_automatic_login', false);
if($isAutomaticLoginEnabled === "true"){
    $isAutomaticLoginEnabled = true;
}else{
    $isAutomaticLoginEnabled = false;
}

if($isAutomaticLoginEnabled){
    $outParams.= '?x-auth-token='.ConfigHelper::getConfig('omi.olt_manager_token');
    $outParams.= '&x-auth-additional-token='.$omi->getMyToken();

}

header("Location: " . $omurl . '/' . $url . $outParams);
die;