<?php

require_once 'DataProvider/NetDevProvider.php';
require_once 'Converter/DescriptionToAddressConverter.php';

require_once 'DataProvider/AddressProvider.php';
require_once 'DataProvider/CustomerProvider.php';

class NetworkDeviceConnectionsByDescriptionProvider implements NetworkDeviceConnectionsProviderInterface
{

    private $descriptionToAddressConverter;
    private $addressProvider;
    private $customerProvider;

    public function __construct()
    {
        $this->descriptionToAddressConverter = new DescriptionToAddressConverter();
        $this->addressProvider = new AddressProvider();
        $this->customerProvider = new CustomerProvider();

    }

    public function getNetworkDeviceConnections()
    {
        $onuDeviceConnections = [];

        $onuDeviceConnections = $this->includeDevices($onuDeviceConnections);
        $onuDeviceConnections = $this->includeNetworkDevices($onuDeviceConnections);

        return $onuDeviceConnections;
    }

    public function getNetworkDeviceConnectionsWithError()
    {
        $onuDeviceConnections = [];

        $onuDeviceConnections = $this->includeDevices($onuDeviceConnections, true);
        $onuDeviceConnections = $this->includeNetworkDevices($onuDeviceConnections, true);

        return $onuDeviceConnections;
    }

    private function includeDevices($onuDeviceConnections, $onlyError = false)
    {
        global $LMS, $DB;
        $nodeCollection = $LMS->GetNodeList();

       /* unset(
            $nodeCollection['total'],
            $nodeCollection['order'],
            $nodeCollection['direction'],
            $nodeCollection['totalon'],
            $nodeCollection['totaloff'],
        );*/

        foreach ($nodeCollection as $node) {
            $fullNode = $LMS->GetNode($node['id']);
            $address = $this->descriptionToAddressConverter->convert($node['info'], $onlyError);
            if ((!$address) || (!$onlyError && key_exists('error', $address)) || ($onlyError && !key_exists('error', $address))) {
                continue;
            }

            if (!$onlyError) {
                if (key_exists($address['stringAddress'], $onuDeviceConnections)) {
                    $onuDeviceConnection = $onuDeviceConnections[$address['stringAddress']];
                } else {
                    $onuDeviceConnection = $this->createNewOnuDeviceConnections($address);
                }
            } else {
                $onuDeviceConnection = $this->createNewOnuDeviceConnections($address);
            }

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

            if ($onlyError) {
                $device['error'] = $address;
            }

            array_push($onuDeviceConnection['devices'], $device);

            if ($onlyError) {
                array_push($onuDeviceConnections, $onuDeviceConnection);
            } else {
                $onuDeviceConnections[$address['stringAddress']] = $onuDeviceConnection;
            }
        }

        return $onuDeviceConnections;
    }

    private function includeNetworkDevices($onuDeviceConnections, $onlyError = false)
    {
        global $LMS, $DB;
        $netDevProvider = new NetDevProvider();

        $netDevCollection = $netDevProvider->getNetDevCollection();

       /* unset(
            $netDevCollection['total'],
            $netDevCollection['order'],
            $netDevCollection['direction'],
        );*/

        foreach ($netDevCollection as $netDev) {


            $address = $this->descriptionToAddressConverter->convert($netDev['description'], $onlyError);
            if((!$address) || (!$onlyError && key_exists('error', $address)) || ($onlyError && !key_exists('error', $address))){
                continue;
            }

            if(!$onlyError){
                if(key_exists($address['stringAddress'], $onuDeviceConnections)){
                    $onuDeviceConnection = $onuDeviceConnections[$address['stringAddress']];
                }else{
                    $onuDeviceConnection = $this->createNewOnuDeviceConnections($address);
                }
            }else{
                $onuDeviceConnection = $this->createNewOnuDeviceConnections($address);
            }

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
                    $locationAddressId = $LMS->GetCustomerAddress((int)$ownerId, DEFAULT_LOCATION_ADDRESS);
                    if (!$locationAddressId) {
                        $locationAddressId = $LMS->GetCustomerAddress((int)$ownerId, BILLING_ADDRESS);
                    }
                }else{
                    $nodesCollection = $LMS->GetNetDevLinkedNodes($netDev['id']);
                    foreach ($nodesCollection as $node) {
                        $locationAddressId = $LMS->GetCustomerAddress((int)$node['ownerid'], DEFAULT_LOCATION_ADDRESS);
                        if(!$locationAddressId){
                            $locationAddressId = $LMS->GetCustomerAddress((int)$node['ownerid'], BILLING_ADDRESS);
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
                $nodesCollection = $LMS->GetNetDevLinkedNodes($netDev['id']);
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

            if ($onlyError) {
                $networkDevice['error'] = $address;
            }

            array_push($onuDeviceConnection['networkDevices'], $networkDevice);


            if ($onlyError) {
                array_push($onuDeviceConnections, $onuDeviceConnection);
            } else {
                $onuDeviceConnections[$address['stringAddress']] = $onuDeviceConnection;
            }
        }

        return $onuDeviceConnections;
    }

    private function createNewOnuDeviceConnections($address)
    {
        return [
            'address' => $address,
            'devices' => [],
            'networkDevices' => [],
        ];
    }


}