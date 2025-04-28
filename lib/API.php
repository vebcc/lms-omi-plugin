<?php

require_once 'DataProvider/NetDevProvider.php';
require_once 'DataProvider/AddressProvider.php';
require_once 'DataProvider/CustomerProvider.php';

class API
{
    private $db;
    private $lms;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
        $this->lms = LMS::getInstance();
    }

    public function getFromApi(string $type, array $params = [])
    {
        $object = API::class;
        if(key_exists('lmsDirect', $params)){
            if($params['lmsDirect'] == 1){
                $object = $this->lms;
            }
            unset($params['lmsDirect']);
        }

        if(!method_exists($object, $type)){
            return ['exception' => 'method with name: '. $type . ' dont exist!', 'code' => 20];
        }

        $argsArray =  $this->argsHandler($object, $type, $params);
        if(key_exists( 'exception', $argsArray)){
            return $argsArray;
        }

        return call_user_func_array([$object, $type], $this->argsHandler($object, $type, $params));
    }

    private function argsHandler($object, string $function, array $params): array
    {
        try{
            $r = new ReflectionMethod($object, $function);
        }catch (Exception $e){
            return ['exception' => $e->getMessage(), 'code' => 9999];
        }

        $args = [];

        foreach ($r->getParameters() as $param) {
            $value = null;
            if(key_exists('arg'.$param->getName(), $params)){
                $value = $params['arg'.$param->getName()];
            }

            if(!$value && !$param->isOptional()){
                return ['exception' => 'This function require additional parameter: '.$param->getName()];
            }
            if(!$value && $param->isOptional()){
                array_push($args, null);
                continue;
            }
            $splittedType = explode('\\', ltrim($param->getType(), '?'));
            if(!key_exists(1, $splittedType)){
                array_push($args, $value);
                continue;
            }

            return ['exception' => 'Something went wrong :('];
        }

        return $args;
    }

    private function getNodeCollection(): array
    {
        $nodes = $this->lms->GetNodeList();

        if (!$nodes) {
            return ['exception' => 'Cant get nodes', 'code' => 18];
        }

        foreach ($nodes as $key => $node) {
            $checksum = md5(json_encode($node));
            $nodes[$key]['checksum'] = $checksum;
        }

        return $nodes;
    }

    public function getNodeChecksum()
    {
        $nodes = $this->lms->GetNodeList();
        if (!$nodes) {
            return ['exception' => 'Cant get nodes', 'code' => 18];
        }

        return md5(json_encode($nodes));
    }

    private function getNetDevCollection(): array
    {
        $netDevProvider = new NetDevProvider();
        $addressProvider = new AddressProvider();
        $customerProvider = new CustomerProvider();

        $netDevCollection = $netDevProvider->getNetDevCollection();

        unset(
            $netDevCollection['total'],
            $netDevCollection['order'],
            $netDevCollection['direction'],
        );

        if (!$netDevCollection) {
            return ['exception' => 'Cant get network devices', 'code' => 18];
        }

        foreach ($netDevCollection as $key => $netDev) {
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
                    $locationAddressIdents = $addressProvider->getAddressByAddressId($locationAddressId);

                    $locationAddress = [
                        'cityIdent' => (int)$locationAddressIdents['cityIdent'],
                        'streetIdent' => (int)$locationAddressIdents['streetIdent'],
                        'location_house' => $locationAddressIdents['house']
                    ];
                }
            }

            $locationAddress['longitude'] = $netDev['longitude'];
            $locationAddress['latitude'] = $netDev['latitude'];

            $netDevCollection[$key]['locationAddress'] = $locationAddress;

            $checksum = md5(json_encode($netDevCollection[$key]));
            $netDevCollection[$key]['checksum'] = $checksum;
        }

        return $netDevCollection;
    }

    private function getNetDevChecksum()
    {
        $netDev = $this->getNetDevCollection();
        if (!$netDev) {
            return ['exception' => 'Cant get network devices', 'code' => 18];
        }

        return md5(json_encode($netDev));
    }

    private function getNetNodeCollection(): array
    {
        $netNode = $this->lms->GetNetNodeList([], []);

        if (!$netNode) {
            return ['exception' => 'Cant get network nodes', 'code' => 18];
        }

        foreach ($netNode as $key => $node) {
            $checksum = md5(json_encode($node));
            $netNode[$key]['checksum'] = $checksum;
        }

        return $netNode;
    }

    private function getNetNodeChecksum()
    {
        $netNode = $this->lms->GetNetNodeList([], []);
        if (!$netNode) {
            return ['exception' => 'Cant get network nodes', 'code' => 18];
        }

        return md5(json_encode($netNode));
    }

    private function getCustomerList(): array
    {
        $addressProvider = new AddressProvider();

        $customers = $this->lms->getCustomerList([]);

        if (!$customers) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }

        foreach ($customers as $key => $customer) {
            $locationAddress = [];
            $locationAddressId = $this->lms->GetCustomerAddress((int)$customer['id'], DEFAULT_LOCATION_ADDRESS);
            if (!$locationAddressId) {
                $locationAddressId = $this->lms->GetCustomerAddress((int)$customer['id'], BILLING_ADDRESS);
            }

            if ($locationAddressId) {
                $locationAddressIdents = $addressProvider->getAddressByAddressId($locationAddressId);

                $locationAddress = [
                    'cityIdent' => (int)$locationAddressIdents['cityIdent'],
                    'streetIdent' => (int)$locationAddressIdents['streetIdent'],
                    'location_house' => $locationAddressIdents['house']
                ];
            }

            $customers[$key]['locationAddress'] = $locationAddress;
            $checksum = md5(json_encode($customers[$key]));
            $customers[$key]['checksum'] = $checksum;
        }

        return $customers;
    }

    private function getCustomerChecksum()
    {
        $customers = $this->getCustomerList();
        if (!$customers) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }

        return md5(json_encode($customers));
    }

    private function getTariffs(): array
    {
        $tariffs = $this->lms->GetTariffs();

        if (!$tariffs) {
            return ['exception' => 'Cant get tariffs', 'code' => 18];
        }

        foreach ($tariffs as $key => $tariff) {
            $checksum = md5(json_encode($tariff));
            $tariffs[$key]['checksum'] = $checksum;
        }

        return $tariffs;
    }

    public function getTariffsChecksum()
    {
        $tariffs = $this->lms->GetTariffs();
        if (!$tariffs) {
            return ['exception' => 'Cant get tariffs', 'code' => 18];
        }

        return md5(json_encode($tariffs));
    }

}