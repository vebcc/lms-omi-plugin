<?php

set_include_path(PLUG_DIR . DIRECTORY_SEPARATOR . 'LMSOmiPlugin'
    . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());

require_once 'NetworkDeviceConnections/NetworkDeviceConnectionsProviderInterface.php';
require_once 'NetworkDeviceConnections/NetworkDeviceConnectionsByDescriptionProvider.php';
require_once 'API.php';
require_once 'DataProvider/UserProvider.php';
require_once 'DataProvider/PPPoECredentialsProvider.php';
require_once 'DataProvider/DeviceProvider.php';
require_once 'DataProvider/NetworkDeviceProvider.php';

class OMI
{
    /*    private $db;
        private $lms;*/

    /*    public function __construct()
        {
            $this->db = LMSDB::getInstance();
            $this->lms = LMS::getInstance();
        }*/

    public function getFromOmiModule($type, $params)
    {
        switch ($type) {
            default:
                $result = $this->getDefaultResult();
                break;
            case 'getNetworkDeviceConnections':
                $result = $this->getNetworkDeviceConnections($params);
                break;
            case 'getNetworkDeviceConnectionsWithError':
                $result = $this->getNetworkDeviceConnectionsWithError($params);
                break;
            case 'getMyToken':
                $result = [$this->getMyToken()];
                break;
            case 'getMyLogin':
                $result = [$this->getMyLogin()];
                break;
            case 'getUserTokens':
                $result = $this->getUserTokens();
                break;
            case 'getPPPoECredentials':
                $result = $this->getPPPoECredentials($params);
                break;
            case 'getDevices':
                $result = $this->getDevices();
                break;
            case 'getNetworkDevices':
                $result = $this->getNetworkDevices();
                break;
            case 'getGponZtePluginOnus':
                $result = $this->getGponZtePluginOnus();
                break;
            case 'getGponZtePluginOnusChecksum':
                $result = $this->getGponZtePluginOnusChecksum();
                break;
            case 'getCustomers':
                $result = $this->getCustomers();
                break;
            case 'getCustomersChecksum':
                $result = $this->getCustomersChecksum();
                break;
            case 'getNodes':
                $result = $this->getNodes();
                break;
            case 'getNodesChecksum':
                $result = $this->getNodesChecksum();
                break;
        }

        return $result;
    }

    public function getFromApiModule($type, $params)
    {
        $api = new API();

        return $api->getFromApi($type, $params);
    }

    private function getDefaultResult()
    {
        return ['exception' => 'type parameter required', 'code' => 10];
    }

    private function getNetworkDeviceConnections($params)
    {
        $version = isset($params['version']) ? $params['version'] : 'description'; //mac and other when isp need.

        $provider = null;

        switch ($version) {
            case 'description':
                $provider = new NetworkDeviceConnectionsByDescriptionProvider();
        }

        if (!$provider) {
            return ['exception' => 'This provider version is not available', 'code' => 11];
        }

        return $provider->getNetworkDeviceConnections();
    }

    private function getNetworkDeviceConnectionsWithError($params)
    {
        $version = isset($params['version']) ? $params['version'] : 'description'; //mac and other when isp need.

        $provider = null;

        switch ($version) {
            case 'description':
                $provider = new NetworkDeviceConnectionsByDescriptionProvider();
        }

        if (!$provider) {
            return ['exception' => 'This provider version is not available', 'code' => 11];
        }

        return $provider->getNetworkDeviceConnectionsWithError();
    }

    public function getPPPoECredentials($params = [])
    {
        $pppoeCredentialsProvider = new PPPoECredentialsProvider();
        $pppoeCredentials = $pppoeCredentialsProvider->getPPPoECredentials($params);
        if (!$pppoeCredentials) {
            return ['exception' => 'Cant get PPPoE credentials for your account', 'code' => 14];
        }
        return $pppoeCredentials;
    }

    public function getMyToken()
    {
        $userProvider = new UserProvider();
        $id = Auth::GetCurrentUser();
        $userToken = $userProvider->getUserToken($id);
        if (!$userToken) {
            return ['exception' => 'Cant get token for your account', 'code' => 12];
        }
        return $userToken;
    }

    private function getUserTokens()
    {
        $userProvider = new UserProvider();
        $userTokenCollection = $userProvider->getUserTokenCollection();
        if (!$userTokenCollection) {
            return ['exception' => 'Cant get tokens', 'code' => 13];
        }
        return $userTokenCollection;
    }

    public function getMyLogin()
    {
        $userProvider = new UserProvider();
        $id = Auth::GetCurrentUser();
        $userLogin = $userProvider->getUserLogin($id);
        if (!$userLogin) {
            return ['exception' => 'Cant get login for your account', 'code' => 12];
        }
        return $userLogin;
    }

    public function getDevices()
    {
        $deviceProvider = new DeviceProvider();
        $devices = $deviceProvider->getDevices();
        if (!$devices) {
            return ['exception' => 'Cant get devices', 'code' => 15];
        }
        return $devices;
    }

    public function getNetworkDevices()
    {
        $networkDeviceProvider = new NetworkDeviceProvider();
        $networkDevices = $networkDeviceProvider->getNetworkDevices();
        if (!$networkDevices) {
            return ['exception' => 'Cant get networkDevices', 'code' => 16];
        }
        return $networkDevices;
    }

    public function getGponZtePluginOnus()
    {
        global $LMS, $DB;

        $onus = $DB->GetAll('SELECT gpononu.id, gpononu.serialnumber, gpononu.nodeid, gpononu.onuid, gpononu2olt.netdevid, gpononu2olt.numport, gpononu2customers.customerid FROM gpononu Left JOIN gpononu2olt ON gpononu2olt.gpononuid = gpononu.id LEFT JOIN gpononu2customers ON gpononu2customers.gpononuid = gpononu.id');

        if (!$onus) {
            return ['exception' => 'Cant get onus', 'code' => 17];
        }

        foreach ($onus as $key => $value) {
            $checksum = md5(json_encode($value));
            $onus[$key]['checksum'] = $checksum;
        }

        return $onus;
    }

    public function getGponZtePluginOnusChecksum()
    {
        global $LMS, $DB;

        $onus = $DB->GetAll('SELECT gpononu.id, gpononu.serialnumber, gpononu.nodeid, gpononu.onuid, gpononu2olt.netdevid, gpononu2olt.numport, gpononu2customers.customerid FROM gpononu Left JOIN gpononu2olt ON gpononu2olt.gpononuid = gpononu.id LEFT JOIN gpononu2customers ON gpononu2customers.gpononuid = gpononu.id');

        if (!$onus) {
            return ['exception' => 'Cant get onus', 'code' => 18];
        }
        return md5(json_encode($onus));
    }

    public function getCustomers()
    {
        global $LMS, $DB;

        $customers = $DB->GetAll('SELECT c.id, c.lastname, c.name, c.email, c.address, c.zip, c.city, c.countryid, c.post_name, c.post_address, c.post_city, c.post_countryid, c.deleted, c.invoice_address, c.invoice_zip, c.invoice_city, c.invoice_countryid, c.recipient_lastname, c.recipient_name, c.recipient_zip, c.recipient_city FROM customers as c;');

        if (!$customers) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }

        foreach ($customers as $key => $customer) {
            $checksum = md5(json_encode($customer));
            $customers[$key]['checksum'] = $checksum;
        }

        return $customers;
    }

    public function getCustomersChecksum()
    {
        global $LMS, $DB;

        $customers = $DB->GetAll('SELECT c.id, c.lastname, c.name, c.email, c.address, c.zip, c.city, c.countryid, c.post_name, c.post_address, c.post_city, c.post_countryid, c.deleted, c.invoice_address, c.invoice_zip, c.invoice_city, c.invoice_countryid, c.recipient_lastname, c.recipient_name, c.recipient_zip, c.recipient_city FROM customers as c;');

        if (!$customers) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }
        return md5(json_encode($customers));
    }

    public function getNodes()
    {
        global $LMS, $DB;

        $nodes = $DB->getAll('SELECT n.id, n.name, inet_ntoa(n.ipaddr) AS ip, n.ipaddr, n.ownerid, n.creationdate, n.moddate, n.netdev, n.location, n.location_city, n.location_street, n.location_house, n.location_flat, n.longitude, n.latitude, n.producer, n.model, n.sn, n.terc, n.simc, n.ulic, n.zip FROM nodes as n');

        if (!$nodes) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }

        foreach ($nodes as $key => $node) {
            $checksum = md5(json_encode($node));
            $nodes[$key]['checksum'] = $checksum;
        }

        return $nodes;
    }

    public function getNodesChecksum()
    {
        global $LMS, $DB;

        $nodes = $DB->getAll('SELECT n.id, n.name, inet_ntoa(n.ipaddr) AS ip, n.ipaddr, n.ownerid, n.creationdate, n.moddate, n.netdev, n.location, n.location_city, n.location_street, n.location_house, n.location_flat, n.longitude, n.latitude, n.producer, n.model, n.sn, n.terc, n.simc, n.ulic, n.zip FROM nodes as n');

        if (!$nodes) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }
        return md5(json_encode($nodes));
    }
}