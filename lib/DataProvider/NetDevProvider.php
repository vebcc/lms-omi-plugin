<?php

require_once 'Repository/NetDevRepository.php';

class NetDevProvider
{
    private $repository;

    public function __construct()
    {
        $this->repository = new NetDevRepository();

    }

    public function getNetDevCollection()
    {
        global $LMS, $DB;
        $netDevCollection = $this->repository->findNetDevCollection();

        if ($netDevCollection) {
            $customerManager = new LMSCustomerManager($LMS->getDb(), $LMS->getAuth(), $LMS->getCache(), $LMS->getSyslog());

            foreach ($netDevCollection as &$netDev) {
                $netDev['customlinks'] = [];
                if (!$netDev['location'] && $netDev['ownerid']) {
                    $netDev['location'] = $customerManager->getAddressForCustomerStuff($netDev['ownerid']);
                }
                $netDev['terc'] = empty($netDev['state_ident']) ? null
                    : $netDev['state_ident'] . $netDev['district_ident']
                    . $netDev['borough_ident'] . $netDev['borough_type'];
                $netDev['simc'] = empty($netDev['city_ident']) ? null : $netDev['city_ident'];
                $netDev['ulic'] = empty($netDev['street_ident']) ? null : $netDev['street_ident'];
                $netDev['lastonlinedate'] = lastonline_date($netDev['lastonline']);
            }
            unset($netDev);

            return $netDevCollection;
        }

        return [];
    }
}