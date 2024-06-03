<?php

require_once 'DataProvider/AddressProvider.php';
require_once 'DataProvider/CustomerProvider.php';

class DeviceProvider
{
    private $lms;
    private $addressProvider;
    private $customerProvider;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
        $this->addressProvider = new AddressProvider();
        $this->customerProvider = new CustomerProvider();
    }

    public function getDevices(): array
    {
        $devices = [];
        $nodeCollection = $this->lms->GetNodeList();

        unset(
            $nodeCollection['total'],
            $nodeCollection['order'],
            $nodeCollection['direction'],
            $nodeCollection['total'],
            $nodeCollection['totalon'],
            $nodeCollection['totaloff'],
        );

        foreach ($nodeCollection as $node) {
            $fullNode = $this->lms->GetNode($node['id']);

            $owner = [];
            if($node['ownerid']){
                $ownerData = $this->customerProvider->getCustomerById($node['ownerid']);
                $owner = [
                    'id' => (int)$ownerData['id'],
                    'name' => $ownerData['name'],
                    'secondName' => $ownerData['lastname'],
                ];
            }

            $device = [
                'lmsId' => $node['id'] ? (int)$node['id'] : null,
                'name' => $node['name'],
                'netDev' => $node['netdev'] ? (int)$node['netdev'] : null,
                'netNode' => $node['netnodeid'] ? (int)$node['netnodeid'] : null,
                'login' => $fullNode['name'],
                'password' => $fullNode['passwd'],
                'data' => [
                    'ip' => $node['ip'],
                    'mac' => $node['mac'],
                    'serial' => null,
                    'producer' => null,
                    'model' => null,
                ],
                'owner' => $owner,
                'address' => [
                    'cityIdent' => (int)$node['city_ident'],
                    'streetIdent' => (int)$node['street_ident'],
                    'location_house' => $node['location_house'],
                    'longitude' => $node['longitude'],
                    'latitude' => $node['latitude'],
                ],
            ];

            $devices[$node['id']] = $device;
        }

        return $devices;
    }
}