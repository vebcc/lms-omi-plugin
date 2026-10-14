<?php

declare(strict_types=1);

namespace Omi\Security;

/**
 * Token CSRF dla akcji ONU (omiapiaction) przechowywany w sesji LMS.
 */
final class CsrfTokenManager
{
    public const SESSION_KEY = 'omi_csrf_token';
    public const HEADER_NAME = 'X-OMI-CSRF-Token';
    public const SERVER_KEY = 'HTTP_X_OMI_CSRF_TOKEN';

    private SessionStore $session;

    public function __construct(SessionStore $session)
    {
        $this->session = $session;
    }

    /**
     * Zwraca token sesji, tworząc go przy pierwszym użyciu.
     */
    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!self::isWellFormed($token)) {
            $token = bin2hex(random_bytes(32));
            $this->session->save(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * @param mixed $candidate
     */
    public function isValid($candidate): bool
    {
        $token = $this->session->get(self::SESSION_KEY);

        return self::isWellFormed($token) && is_string($candidate) && hash_equals($token, $candidate);
    }

    /**
     * @param mixed $token
     * @phpstan-assert-if-true string $token
     */
    private static function isWellFormed($token): bool
    {
        return is_string($token) && preg_match('/^[0-9a-f]{64}$/', $token) === 1;
    }
}
