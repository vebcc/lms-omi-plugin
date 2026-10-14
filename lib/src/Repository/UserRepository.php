<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * Dane użytkowników LMS potrzebne do logowania w OltManagerze (SSO).
 */
class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function loginById(int $userId): ?string
    {
        $login = $this->db->getOne('SELECT login FROM users WHERE id = ?', [$userId]);

        return $login === null || $login === false || $login === '' ? null : (string) $login;
    }
}
