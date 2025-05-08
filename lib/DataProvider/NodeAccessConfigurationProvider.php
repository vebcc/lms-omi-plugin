<?php

require_once 'Repository/NodeAccessConfigurationRepository.php';

class NodeAccessConfigurationProvider
{
    private $lms;
    private $repository;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
        $this->repository = new NodeAccessConfigurationRepository();

    }

    public function getNodeAccessConfigurationCollection($ignoreGroups = [], $at = null): ?array
    {
        if(empty($ignoreGroups)) {
            $ignoreGroupsString = ConfigHelper::getConfig('omi.node_access_configuration_ignore_groups', 'null');
            if ($ignoreGroupsString !== 'null') {
                $ignoreGroups = explode(',', $ignoreGroupsString);
            }
        }

        if($at === null) {
            $at = ConfigHelper::getConfig('omi.node_access_configuration_at', 'null');
            if ($at === 'null') {
                $at = null;
            }
        }

        $result =  $this->repository->findNodeAccessConfigurationCollection($ignoreGroups, $at);

        return $result;
    }
}