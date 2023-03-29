<?php

class NetworkDeviceConnectionsByDescriptionProvider implements NetworkDeviceConnectionsProviderInterface
{
    private $db;
    private $lms;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
        $this->lms = LMS::getInstance();

    }

    public function getNetworkDeviceConnections(): array
    {
        $deviceCollection = $this->getDevice();

        $onuDeviceConnections = [
            ''
        ];

        $onuDeviceConnections = [];

        $onuAddress = 'CZ_1/2/1:33';
        $onuDeviceConnections[$onuAddress] = ['onuAddress' => $onuAddress];

        return $onuDeviceConnections;
    }

    private function getDevice(): array
    {
        $nodeCollection = $this->lms->GetNodeList();


        return $nodeCollection;
    }

    private function getNetworkDevice()
    {

    }
}