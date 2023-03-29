<?php

require_once 'DataProvider/NetDevProvider.php';
require_once 'Converter/DescriptionToAddressConverter.php';

class NetworkDeviceConnectionsByDescriptionProvider implements NetworkDeviceConnectionsProviderInterface
{
    private $lms;

    private $descriptionToAddressConverter;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
        $this->descriptionToAddressConverter = new DescriptionToAddressConverter();
    }

    public function getNetworkDeviceConnections(): array
    {
        $onuDeviceConnections = [];

        //$onuDeviceConnections = $this->includeDevices($onuDeviceConnections);
        $onuDeviceConnections = $this->includeNetworkDevices($onuDeviceConnections);

        return $onuDeviceConnections;
    }

    private function includeDevices(array $onuDeviceConnections): array
    {
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
            $address = $this->descriptionToAddressConverter->convert($node['info']);
            if(!$address){
                continue;
            }

            if(key_exists($address['stringAddress'], $onuDeviceConnections)){
                $onuDeviceConnection = $onuDeviceConnections[$address['stringAddress']];
            }else{
                $onuDeviceConnection = $this->createNewOnuDeviceConnections($address);
            }

            $device = [
                'lmsId' => (int)$node['id'],
                'ownerId' => (int)$node['ownerid'],
                'netDev' => (int)$node['netdev'],
                'netNode' => (int)$node['netnodeid'],
                'address' => [
                    'longitude' => $node['longitude'],
                    'latitude' => $node['latitude'],
                    'cityIdent' => (int)$node['city_ident'],
                    'stateIdent' => (int)$node['state_ident'],
                    'streetIdent' => (int)$node['street_ident'],
                    'terc' => (int)$node['terc'],
                    'simc' => (int)$node['simc'],
                    'ulic' => (int)$node['ulic'],
                    'location_house' => $node['location_house']
                ],
            ];

            array_push($onuDeviceConnection['devices'], $device);

            $onuDeviceConnections[$address['stringAddress']] = $onuDeviceConnection;
        }

        return $onuDeviceConnections;
    }

    private function includeNetworkDevices(array $onuDeviceConnections): array
    {
        $netDevProvider = new NetDevProvider();

        //$netDevCollection = $this->lms->GetNetDevList();
        $netDevCollection = $netDevProvider->getNetDevCollection();

        unset(
            $netDevCollection['total'],
            $netDevCollection['order'],
            $netDevCollection['direction'],
        );

        foreach ($netDevCollection as $netDev) {
            $address = $this->descriptionToAddressConverter->convert($netDev['description']);
            if(!$address){
                continue;
            }

            if(key_exists($address['stringAddress'], $onuDeviceConnections)){
                $onuDeviceConnection = $onuDeviceConnections[$address['stringAddress']];
            }else{
                $onuDeviceConnection = $this->createNewOnuDeviceConnections($address);
            }

            $locationAddress =  $this->lms->GetCustomerAddress((int)$netDev['ownerid'], DEFAULT_LOCATION_ADDRESS);
            if(!$locationAddress){
                $locationAddress =  $this->lms->GetCustomerAddress((int)$netDev['ownerid'], BILLING_ADDRESS);
            }

            $networkDevice = [
                'lmsId' => $netDev['id'],
                'ownerId' => $netDev['ownerid'],
                'netNode' => $netDev['netnodeid'],
                'producer' => $netDev['producer'],
                'model' => $netDev['model'],
                'serialNumber' => $netDev['serialnumber'],
                'address' => [
                    'longitude' => $netDev['longitude'],
                    'latitude' => $netDev['latitude'],
                    'cityIdent' => $netDev['city_ident'],
                    'stateIdent' => $netDev['state_ident'],
                    'streetIdent' => $netDev['street_ident'],
                    'terc' => $netDev['terc'],
                    'simc' => $netDev['simc'],
                    'ulic' => $netDev['ulic'],
                    'location_house' => $netDev['location_house']
                ],
            ];

            array_push($onuDeviceConnection['networkDevices'], $networkDevice);

            $onuDeviceConnections[$address['stringAddress']] = $onuDeviceConnection;
        }

        return $onuDeviceConnections;
    }

    private function createNewOnuDeviceConnections(array $address): array
    {
        return [
            'address' => $address,
            'devices' => [],
            'networkDevices' => [],
        ];
    }



}