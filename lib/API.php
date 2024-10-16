<?php


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
        $netDev = $this->lms->GetNetDevList();

        if (!$netDev) {
            return ['exception' => 'Cant get network devices', 'code' => 18];
        }

        foreach ($netDev as $key => $dev) {
            $checksum = md5(json_encode($dev));
            $netDev[$key]['checksum'] = $checksum;
        }

        return $netDev;
    }

    private function getNetDevChecksum()
    {
        $netDev = $this->lms->GetNetDevList();
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
        $customers = $this->lms->getCustomerList([]);

        if (!$customers) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }

        foreach ($customers as $key => $customer) {
            $checksum = md5(json_encode($customer));
            $customers[$key]['checksum'] = $checksum;
        }

        return $customers;
    }

    private function getCustomerChecksum()
    {
        $customers = $this->lms->getCustomerList([]);
        if (!$customers) {
            return ['exception' => 'Cant get customers', 'code' => 18];
        }

        return md5(json_encode($customers));
    }

}