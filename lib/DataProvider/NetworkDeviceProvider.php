<?php

require_once 'DataProvider/AddressProvider.php';
require_once 'DataProvider/CustomerProvider.php';

class NetworkDeviceProvider
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

    public function getNetworkDevices(): array
    {
        $networkDevices = [];
        $netDevProvider = new NetDevProvider();

        $netDevCollection = $netDevProvider->getNetDevCollection();

        unset(
            $netDevCollection['total'],
            $netDevCollection['order'],
            $netDevCollection['direction'],
        );

        foreach ($netDevCollection as $netDev) {
            $ownerId = $netDev['ownerid'];

            $locationAddress = [];

            if ($netDev['street_ident'] && $netDev['city_ident']) {
                $locationAddress = [
                    'cityIdent' => $netDev['city_ident'],
                    'streetIdent' => $netDev['street_ident'],
                    'location_house' => $netDev['location_house']
                ];
            } else {
                if($ownerId){
                    $locationAddressId = $this->lms->GetCustomerAddress((int)$ownerId, DEFAULT_LOCATION_ADDRESS);
                    if (!$locationAddressId) {
                        $locationAddressId = $this->lms->GetCustomerAddress((int)$ownerId, BILLING_ADDRESS);
                    }
                }else{
                    $nodesCollection = $this->lms->GetNetDevLinkedNodes($netDev['id']);
                    if(!$nodesCollection){
                        continue;
                    }
                    foreach ($nodesCollection as $node) {
                        $locationAddressId = $this->lms->GetCustomerAddress((int)$node['ownerid'], DEFAULT_LOCATION_ADDRESS);
                        if(!$locationAddressId){
                            $locationAddressId = $this->lms->GetCustomerAddress((int)$node['ownerid'], BILLING_ADDRESS);
                        }
                        $ownerId = $node['ownerid'];
                        if($locationAddressId){
                            break;
                        }
                    }
                }

                if ($locationAddressId) {
                    $locationAddressIdents = $this->addressProvider->getAddressByAddressId($locationAddressId);

                    $locationAddress = [
                        'cityIdent' => (int)$locationAddressIdents['cityIdent'],
                        'streetIdent' => (int)$locationAddressIdents['streetIdent'],
                        'location_house' => $locationAddressIdents['house']
                    ];
                }
            }

            if(!$ownerId){
                $nodesCollection = $this->lms->GetNetDevLinkedNodes($netDev['id']);
                if(!$nodesCollection){
                    continue;
                }
                foreach ($nodesCollection as $node) {
                    $ownerId = $node['ownerid'];
                    if($ownerId){
                        break;
                    }
                }
            }

            $locationAddress['longitude'] = $netDev['longitude'];
            $locationAddress['latitude'] = $netDev['latitude'];

            $owner = [];
            if($ownerId){
                $ownerData = $this->customerProvider->getCustomerById($ownerId);
                $owner = [
                    'id' => (int)$ownerData['id'],
                    'name' => $ownerData['name'],
                    'secondName' => $ownerData['lastname'],
                ];
            }

            $networkDevice = [
                'lmsId' => $netDev['id'] ? (int)$netDev['id'] : null,
                'name' => $netDev['name'],
                'netNode' => $netDev['netnodeid'] ? (int)$netDev['netnodeid'] : null,
                'data' => [
                    'ip' => null,
                    'mac' => null,
                    'serial' => $netDev['serialnumber'],
                    'producer' => $netDev['producer'],
                    'model' => $netDev['model'],
                ],
                'owner' => $owner,
                'address' => $locationAddress,
            ];

            $networkDevices[$netDev['id']] = $networkDevice;

        }

        return $networkDevices;
    }

}