<?php

use Utils\MACAddressCorrection;

require_once 'CustomerProvider.php';
require_once 'Utils/MacAddressCorrection.php';

class PPPoECredentialsProvider
{
    private $lms;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
    }

    public function getPPPoECredentials(array $params = []): ?array
    {
        if (key_exists('mac', $params)) {
            return $this->getPPPoECredentialsByMacAddress($params['mac'], $params);
        }elseif (key_exists('nodeId', $params)) {
            return $this->getPPPoeCredentialsByNodeId($params['nodeId'], $params);
        }

        return null;
    }

    public function getPPPoeCredentialsByNodeId(int $id, array $params): ?array
    {
        $node = $this->lms->GetNode($id);
        if (!$node) {
            return null;
        }

        return $this->getDataFromNode($node);
    }

    public function getPPPoECredentialsByMacAddress(string $mac, array $params): ?array
    {
        $macCollection[] = $mac;

        if (key_exists('upMacs', $params)) {
            for ($i = 1; $i <= (int)$params['upMacs']; $i++) {
                $macCollection[] = MacAddressCorrection::correct(MACAddressCorrection::modifyMacAddress($mac, $i), 'canonical');
            }
        }

        if (key_exists('downMacs', $params)) {
            for ($i = 1; $i <= (int)$params['downMacs']; $i++) {
                $macCollection[] = MacAddressCorrection::correct(MACAddressCorrection::modifyMacAddress($mac, $i * -1), 'canonical');
            }
        }

        foreach ($macCollection as $mac) {
            $nodeId = $this->lms->GetNodeIDByMAC($mac);
            if (!$nodeId) {
                continue;
            }

            $node = $this->lms->GetNode($nodeId);
            if (!$node) {
                continue;
            }

            return $this->getDataFromNode($node);
        }
        return null;
    }

    private function getDataFromNode($node): ?array
    {
        $mgmntUrls = $this->lms->GetManagementUrls(LMSNetDevManager::NODE_URL, $node['id']);

        $wifiName = ConfigHelper::getConfig('omi.wifi_prefix', 'Internet-');
        $wifi5Adition = ConfigHelper::getConfig('omi.wifi_5_suffix', '_5G');

        $wifiPassword = substr(md5($node['passwd']), 0, 8);

        //Default configuration
        $configuration = [
            'net' => [
                "username" => $node['name'],
                "password" => $node['passwd'],
                "vlan" => ConfigHelper::getConfig('omi.net_vlan', null),
            ],
            "tv" => [
                "enable" => false,
                "vlan" => ConfigHelper::getConfig('omi.tv_vlan', null),
                "ports" => explode(',', ConfigHelper::getConfig('omi.tv_ports', '4')),
            ],
            "wifi" => [
                2 => [
                    "enable" => true,
                    "ssid" => $wifiName . $node['ownerid'],
                    "password" => $wifiPassword,
                ],
                5 => [
                    "enable" => true,
                    "ssid" => $wifiName . $node['ownerid'] . $wifi5Adition,
                    "password" => $wifiPassword,
                ],
            ],
        ];

        //Configuration from mgmnt urls
        $urlConfiguration = [];
        foreach ($mgmntUrls as $mgmntUrl) {
            $url = str_replace("http://", "", $mgmntUrl['url']);
            $urlConfiguration[$url] = $mgmntUrl['comment'];
        }

        //SESSION
        if (key_exists('conf.net.vlan', $urlConfiguration)) {
            $configuration['net']['vlan'] = $urlConfiguration['conf.net.vlan'];
        }

        //WIFI
        if (key_exists('conf.wifi.2.ssid', $urlConfiguration)) {
            $configuration['wifi'][2]['ssid'] = $urlConfiguration['conf.wifi.ssid'];
            $configuration['wifi'][5]['ssid'] = $urlConfiguration['conf.wifi.ssid'] . $wifi5Adition;
        }

        if (key_exists('conf.wifi.5.ssid', $urlConfiguration)) {
            $configuration['wifi'][5]['ssid'] = $urlConfiguration['conf.wifi.5.ssid'];
        }

        if (key_exists('conf.wifi.2.pass', $urlConfiguration)) {
            $configuration['wifi'][2]['password'] = $urlConfiguration['conf.wifi.2.pass'];
            $configuration['wifi'][5]['password'] = $urlConfiguration['conf.wifi.2.pass'];
        }

        if (key_exists('conf.wifi.5.pass', $urlConfiguration)) {
            $configuration['wifi'][5]['password'] = $urlConfiguration['conf.wifi.5.pass'];
        }

        if (key_exists('conf.wifi.2.enable', $urlConfiguration)) {
            $configuration['wifi'][2]['enable'] = $urlConfiguration['conf.wifi.2.enable'];
            $configuration['wifi'][5]['enable'] = $urlConfiguration['conf.wifi.2.enable'];
        }

        if (key_exists('conf.wifi.5.enable', $urlConfiguration)) {
            $configuration['wifi'][5]['enable'] = $urlConfiguration['conf.wifi.5.enable'];
        }

        //TV
        if (key_exists('conf.tv.enable', $urlConfiguration)) {
            $configuration['tv']['enable'] = $urlConfiguration['conf.tv.enable'];
        }

        if (key_exists('conf.tv.vlan', $urlConfiguration)) {
            $configuration['tv']['vlan'] = $urlConfiguration['conf.tv.vlan'];
        }

        if($configuration['tv']['enable'] && $configuration['tv']['vlan'] === null) {
            return ['exception' => 'Cant get PPPoE credentials for your account. Invalid TV VLAN configuration', 'code' => 17];
        }

        if(key_exists('conf.tv.ports', $urlConfiguration)) {
            $configuration['tv']['ports'] = explode(',', $urlConfiguration['conf.tv.ports']);
        }

        return $configuration;
    }

}