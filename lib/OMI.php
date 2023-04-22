<?php

set_include_path(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSOmiPlugin::PLUGIN_DIRECTORY_NAME
    . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());

require_once 'NetworkDeviceConnections/NetworkDeviceConnectionsProviderInterface.php';
require_once 'NetworkDeviceConnections/NetworkDeviceConnectionsByDescriptionProvider.php';
require_once 'API.php';
require_once 'DataProvider/UserProvider.php';

class OMI
{
    private $db;
    private $lms;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
        $this->lms = LMS::getInstance();
    }

    public function getFromOmiModule(string $type, array $params): array
    {
        switch ($type) { //ready to match php 8.0
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
                $result = $this->getMyToken();
                break;
            case 'getUserTokens':
                $result = $this->getUserTokens();
                break;
        }

        return $result;
    }

    public function getFromApiModule(string $type, array $params): array
    {
        $api = new API();

        return $api->getFromApi($type, $params);
    }

    private function getDefaultResult(): array
    {
        return ['exception' => 'type parameter required', 'code' => 10];
    }

    private function getNetworkDeviceConnections(array $params): array
    {
        $version = $params['version'] ?? 'description'; //mac and other when isp need.

        $provider = null;

        switch ($version){
            case 'description':
                $provider = new NetworkDeviceConnectionsByDescriptionProvider();
        }

        if (!$provider){
            return ['exception' => 'This provider version is not available', 'code' => 11];
        }

        return $provider->getNetworkDeviceConnections();
    }

    private function getNetworkDeviceConnectionsWithError(array $params): array
    {
        $version = $params['version'] ?? 'description'; //mac and other when isp need.

        $provider = null;

        switch ($version){
            case 'description':
                $provider = new NetworkDeviceConnectionsByDescriptionProvider();
        }

        if (!$provider){
            return ['exception' => 'This provider version is not available', 'code' => 11];
        }

        return $provider->getNetworkDeviceConnectionsWithError();
    }

    private function getMyToken(): array
    {
        $userProvider = new UserProvider();
        $id = Auth::GetCurrentUser();
        $userToken = $userProvider->getUserToken($id);
        if(!$userToken){
            return ['exception' => 'Cant get token for your account', 'code' => 12];
        }
        return [$userToken];
    }

    private function getUserTokens(): array
    {
        $userProvider = new UserProvider();
        $userTokenCollection = $userProvider->getUserTokenCollection();
        if(!$userTokenCollection){
            return ['exception' => 'Cant get tokens', 'code' => 13];
        }
        return $userTokenCollection;
    }
}