<?php

$layout['pagetitle'] = trans('OMI - Lista urządzeń z błędami');

$omi = LMSOmiPlugin::getOmiInstance();

$providerType = ConfigHelper::getConfig('omi.provider_type', 'description');

//TODO: dodac jakies ustawienia jakiego typu uzywa dany lms (version)
$onuDeviceConnections = $omi->getFromOmiModule('getNetworkDeviceConnectionsWithError', ['version' => $providerType]);

$page = (!$_GET['page'] ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('omi.deviceerrorlist_pagelimit', count($onuDeviceConnections));
$start = ($page - 1) * $pagelimit;

$errorDevices = [];

if (!empty($onuDeviceConnections)) {
    foreach ($onuDeviceConnections as $idx => $onuDeviceConnection) {
        $errorDevice = null;
        if (!empty($onuDeviceConnection['devices'])) {
            $errorDevice = [
                'type' => 'device',
                'lmsId' => $onuDeviceConnection['devices'][0]['lmsId'],
                'error' => $onuDeviceConnection['devices'][0]['error']['error'],
                'address' => $onuDeviceConnection['devices'][0]['error']['address'],
                'name' => $LMS->GetNodeName($onuDeviceConnection['devices'][0]['lmsId']),
                'link' => [
                    'show' => '?m=nodeinfo&id=' . $onuDeviceConnection['devices'][0]['lmsId'],
                    'edit' => '?m=nodeedit&id=' . $onuDeviceConnection['devices'][0]['lmsId'],
                ],
            ];
        } elseif (!empty($onuDeviceConnection['networkDevices'])) {
            $errorDevice = [
                'type' => 'networkDevice',
                'lmsId' => $onuDeviceConnection['networkDevices'][0]['lmsId'],
                'error' => $onuDeviceConnection['networkDevices'][0]['error']['error'],
                'address' => $onuDeviceConnection['networkDevices'][0]['error']['address'],
                'name' => $LMS->GetNetDevName($onuDeviceConnection['networkDevices'][0]['lmsId']),
                'link' => [
                    'show' => '?m=netdevinfo&id=' . $onuDeviceConnection['networkDevices'][0]['lmsId'],
                    'edit' => '?m=netdevedit&id=' . $onuDeviceConnection['networkDevices'][0]['lmsId'],
                ],
            ];
        }

        if (!$errorDevice) {
            $errorDevice = [
                'type' => 'error',
                'lmsId' => null,
                'error' => 'libraryFailed',
                'address' => null,
                'name' => null,
                'link' => [
                    'show' => '#',
                    'edit' => '#',
                ],
            ];
        }

        array_push($errorDevices, $errorDevice);
    }
}

$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', $start);
$SMARTY->assign('errorDevices', $errorDevices);
$SMARTY->assign('networkDeviceConnections', $onuDeviceConnections);
$SMARTY->display('omideviceerrorlist.html');
