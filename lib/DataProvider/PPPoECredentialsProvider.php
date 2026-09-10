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
        // Znormalizuj adres bazowy do formatu kanonicznego (AA:BB:CC:DD:EE:FF) - dopiero na
        // takim adresie modifyMacAddress liczy poprawnie i dopiero taki adres pasuje do bazy LMS.
        $baseMac = MacAddressCorrection::correct($mac, 'canonical');
        if (!$baseMac) {
            return null;
        }

        $macCollection = [$baseMac];

        if (key_exists('upMacs', $params)) {
            for ($i = 1; $i <= (int)$params['upMacs']; $i++) {
                $macCollection[] = MacAddressCorrection::correct(MacAddressCorrection::modifyMacAddress($baseMac, $i), 'canonical');
            }
        }

        if (key_exists('downMacs', $params)) {
            for ($i = 1; $i <= (int)$params['downMacs']; $i++) {
                $macCollection[] = MacAddressCorrection::correct(MacAddressCorrection::modifyMacAddress($baseMac, $i * -1), 'canonical');
            }
        }

        foreach ($macCollection as $candidateMac) {
            $nodeId = $this->lms->GetNodeIDByMAC($candidateMac);
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
        return [
            "username" => $node['name'],
            "password" => $node['passwd'],
        ];
    }

}