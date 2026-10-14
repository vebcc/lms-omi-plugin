<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;
use Omi\Sso\LegacyTokenProvider;

/**
 * Dane użytkowników LMS potrzebne do logowania w OltManagerze.
 */
class UserRepository implements LegacyTokenProvider
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

    /**
     * Stary token autologowania: login$md5(hash hasła).
     *
     * @deprecated tylko dla starych OltManagerów bez SSO (omi.legacy_login_fallback)
     */
    public function tokenForUser(int $userId): ?string
    {
        $row = $this->db->getRow('SELECT login, passwd FROM users WHERE id = ?', [$userId]);
        if ($row === null || (string) $row['login'] === '') {
            return null;
        }

        return self::legacyToken((string) $row['login'], (string) $row['passwd']);
    }

    /**
     * @deprecated tylko dla legacy omidatagetter&type=getUserTokens
     * @return array<string, string> login => token
     */
    public function legacyTokensOfActiveUsers(): array
    {
        $tokens = [];
        foreach ($this->db->getAll('SELECT login, passwd FROM vusers WHERE deleted = 0 ORDER BY login') as $row) {
            $tokens[(string) $row['login']] = self::legacyToken((string) $row['login'], (string) $row['passwd']);
        }

        return $tokens;
    }

    public static function legacyToken(string $login, string $passwordHash): string
    {
        return $login . '$' . md5($passwordHash);
    }
}
