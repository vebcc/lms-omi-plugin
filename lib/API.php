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

    public function getFromApi(string $type, array $params = []): array
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

            return ['exception' => 'Something went wrong :('];
        }

        return $args;
    }

    private function getNodeCollection(): array
    {
        return $this->lms->GetNodeList();
    }

    private function getNetDevCollection(): array
    {
        return $this->lms->GetNetDevList();
    }

    private function getNetNodeCollection(): array
    {
        return $this->lms->GetNetNodeList([], []);
    }

}