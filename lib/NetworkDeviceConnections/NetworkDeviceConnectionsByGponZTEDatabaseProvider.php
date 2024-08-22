<?php

namespace NetworkDeviceConnections;
use AddressProvider;
use CustomerProvider;
use DescriptionToAddressConverter;
use LMS;
use NetDevProvider;
use NetworkDeviceConnectionsProviderInterface;

require_once 'DataProvider/NetDevProvider.php';
require_once 'Converter/DescriptionToAddressConverter.php';

require_once 'DataProvider/AddressProvider.php';
require_once 'DataProvider/CustomerProvider.php';

class NetworkDeviceConnectionsByGponZTEDatabaseProvider implements NetworkDeviceConnectionsProviderInterface
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

        return $onuDeviceConnections;
    }

    public function getNetworkDeviceConnectionsWithError()
    {
        $onuDeviceConnections = [];

        $onuDeviceConnections = $this->includeDevices($onuDeviceConnections, true);

        return $onuDeviceConnections;
    }

    private function includeDevices($onuDeviceConnections, $onlyError = false)
    {
        global $LMS, $DB;
        $nodeCollection = $LMS->GetNodeList();

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
            if ($node['ownerid']) {
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

    private function createNewOnuDeviceConnections($address)
    {
        return [
            'address' => $address,
            'devices' => [],
            'networkDevices' => [],
        ];
    }


}