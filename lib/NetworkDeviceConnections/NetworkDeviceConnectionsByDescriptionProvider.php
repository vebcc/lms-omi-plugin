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

        $onuDeviceConnections = $this->includeDevices($onuDeviceConnections);
        $onuDeviceConnections = $this->includeNetworkDevices($onuDeviceConnections);

        return $onuDeviceConnections;
    }

    public function getNetworkDeviceConnectionsWithError(): array
    {
        $onuDeviceConnections = [];

        $onuDeviceConnections = $this->includeDevices($onuDeviceConnections, true);
        $onuDeviceConnections = $this->includeNetworkDevices($onuDeviceConnections, true);

        return $onuDeviceConnections;
    }

    private function includeDevices(array $onuDeviceConnections, bool $onlyError = false): array
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
            $address = $this->descriptionToAddressConverter->convert($node['info'], $onlyError);
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


            $device = [
                'lmsId' => $node['id'],
                'ownerId' => $node['ownerid'],
                'netDev' => $node['netdev'],
                'netNode' => $node['netnodeid'],
                'address' => [
                    'longitude' => $node['longitude'],
                    'latitude' => $node['latitude'],
                    'cityIdent' => $node['city_ident'],
                    'stateIdent' => $node['state_ident'],
                    'streetIdent' => $node['street_ident'],
                    'location_house' => $node['location_house']
                ],
            ];

            if($onlyError){
                $device['error'] = $address;
            }

            array_push($onuDeviceConnection['devices'], $device);

            if($onlyError){
                array_push($onuDeviceConnections, $onuDeviceConnection);
            }else{
                $onuDeviceConnections[$address['stringAddress']] = $onuDeviceConnection;
            }
        }

        return $onuDeviceConnections;
    }

    private function includeNetworkDevices(array $onuDeviceConnections, bool $onlyError = false): array
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
                    'location_house' => $netDev['location_house']
                ],
            ];

            if($onlyError){
                $networkDevice['error'] = $address;
            }

            array_push($onuDeviceConnection['networkDevices'], $networkDevice);


            if($onlyError){
                array_push($onuDeviceConnections, $onuDeviceConnection);
            }else{
                $onuDeviceConnections[$address['stringAddress']] = $onuDeviceConnection;
            }
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