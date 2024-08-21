<?php

require_once(PLUG_DIR.'/LMSOmiPlugin/lib/OMI.php');

$params = "";
$omi = new OMI();
$isAutomaticLoginEnabled = get_conf('phpui.olt_manager_automatic_login', false);
if($isAutomaticLoginEnabled === "true"){
    $isAutomaticLoginEnabled = true;
}else{
    $isAutomaticLoginEnabled = false;
}

if($isAutomaticLoginEnabled){
    $params.= '?x-auth-token='.get_conf('phpui.olt_manager_token');
    $params.= '&x-auth-additional-token='.$omi->getMyToken();

}

$SMARTY->assign('omioltmanagerurl', get_conf('phpui.olt_manager_url'));
$SMARTY->assign('omioltmanagertoken', get_conf('phpui.olt_manager_token'));
$SMARTY->assign('omioltmanagerparams', $params);

?>