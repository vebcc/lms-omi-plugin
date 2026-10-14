<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * Pierwszy aktywny (nie wyłączony) kontakt e-mail każdego klienta - jedno zapytanie
 * zamiast GetCustomerEmail() per klient.
 */
class CustomerEmails
{
    private Database $db;
    private int $emailFlag;
    private int $disabledFlag;

    /** @var array<int, string>|null */
    private ?array $emails = null;

    public function __construct(Database $db, ?int $emailFlag = null, ?int $disabledFlag = null)
    {
        $this->db = $db;
        $this->emailFlag = $emailFlag ?? (defined('CONTACT_EMAIL') ? (int) constant('CONTACT_EMAIL') : 8);
        $this->disabledFlag = $disabledFlag ?? (defined('CONTACT_DISABLED') ? (int) constant('CONTACT_DISABLED') : 16384);
    }

    public function firstActiveEmail(int $customerId): ?string
    {
        if ($this->emails === null) {
            $this->emails = [];
            $rows = $this->db->getAll(
                'SELECT customerid, contact FROM customercontacts
                WHERE (type & ?) = ?
                ORDER BY customerid, id',
                [$this->emailFlag | $this->disabledFlag, $this->emailFlag]
            );
            foreach ($rows as $row) {
                $id = (int) $row['customerid'];
                if (!isset($this->emails[$id])) {
                    $this->emails[$id] = (string) $row['contact'];
                }
            }
        }

        return $this->emails[$customerId] ?? null;
    }
}
