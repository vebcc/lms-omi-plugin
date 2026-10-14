<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Config\OmiConfig;
use Omi\Lms\LmsApi;
use Omi\Repository\CustomerAddressBook;
use Omi\Repository\CustomerEmails;

/**
 * Klienci: wynik LMS getCustomerList() + opcjonalnie "email" + "locationAddress".
 */
final class CustomerCollection implements CollectionProvider
{
    private LmsApi $lms;
    private CustomerAddressBook $addresses;
    private CustomerEmails $emails;
    private OmiConfig $config;

    public function __construct(LmsApi $lms, CustomerAddressBook $addresses, CustomerEmails $emails, OmiConfig $config)
    {
        $this->lms = $lms;
        $this->addresses = $addresses;
        $this->emails = $emails;
        $this->config = $config;
    }

    public function name(): string
    {
        return 'customers';
    }

    public function fetchRaw(): array
    {
        $customers = $this->lms->getCustomerList();
        $returnEmail = $this->config->returnCustomerEmail();

        foreach ($customers as $key => $customer) {
            if (!is_array($customer) || !isset($customer['id'])) {
                continue;
            }
            $id = (int) $customer['id'];

            if ($returnEmail) {
                $customers[$key]['email'] = $this->emails->firstActiveEmail($id);
            }
            $customers[$key]['locationAddress'] = $this->addresses->locationAddress($id);
        }

        return $customers;
    }
}
