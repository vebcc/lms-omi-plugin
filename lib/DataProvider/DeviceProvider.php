<?php

require_once 'DataProvider/AddressProvider.php';
require_once 'DataProvider/CustomerProvider.php';

class DeviceProvider
{
    private $addressProvider;
    private $customerProvider;

    public function __construct()
    {
        $this->addressProvider = new AddressProvider();
        $this->customerProvider = new CustomerProvider();
    }

    public function getDevices()
    {
        global $LMS, $DB;
        $devices = [];
        $nodeCollection = $LMS->GetNodeList();

        foreach ($nodeCollection as $node) {
            $fullNode = $LMS->GetNode($node['id']);

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