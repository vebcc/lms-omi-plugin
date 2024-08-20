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
        $SMARTY->assign('omioltmanagerurl', ConfigHelper::getConfig('phpui.olt_manager_url'));
        $SMARTY->assign('omioltmanagertoken', ConfigHelper::getConfig('phpui.olt_manager_token'));
        $SMARTY->assign('omioltmanagerdevice', 'networkDevice');
    }
}