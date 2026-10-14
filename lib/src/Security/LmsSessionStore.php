<?php

declare(strict_types=1);

namespace Omi\Security;

/**
 * Adapter sesji LMS (global $SESSION, odczytywany przy każdym użyciu - kontener usług może
 * powstać wcześniej niż sesja). Zmiany zapisuje LMS w Session::close() na końcu żądania.
 */
final class LmsSessionStore implements SessionStore
{
    public function get(string $name)
    {
        $session = self::session();

        return $session !== null ? $session->get($name) : null;
    }

    public function save(string $name, $value): void
    {
        $session = self::session();
        if ($session !== null) {
            $session->save($name, $value);
        }
    }

    /**
     * @return \Session|null
     */
    private static function session()
    {
        $session = $GLOBALS['SESSION'] ?? null;

        return $session instanceof \Session ? $session : null;
    }
}
