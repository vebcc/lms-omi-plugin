<?php

declare(strict_types=1);

namespace Omi\Lms;

/**
 * Adapter obiektu LMS (LMS::getInstance()).
 */
final class LmsFacade implements LmsApi
{
    /** @var \LMS */
    private $lms;

    /**
     * @param \LMS $lms instancja LMS
     */
    public function __construct($lms)
    {
        $this->lms = $lms;
    }

    public function getNodeList(): array
    {
        return self::arrayOrEmpty($this->lms->GetNodeList());
    }

    public function getCustomerList(): array
    {
        return self::arrayOrEmpty($this->lms->getCustomerList([]));
    }

    public function getTariffs(): array
    {
        return self::arrayOrEmpty($this->lms->GetTariffs());
    }

    public function getNetNodeList(): array
    {
        return self::arrayOrEmpty($this->lms->GetNetNodeList([], 'name,asc'));
    }

    public function getNode(int $id): ?array
    {
        $node = $this->lms->GetNode($id);

        return is_array($node) && $node !== [] ? $node : null;
    }

    public function getNodeIdByMac(string $mac): ?int
    {
        $id = $this->lms->GetNodeIDByMAC($mac);

        return $id ? (int) $id : null;
    }

    public function getNodeName(int $id): string
    {
        return (string) $this->lms->GetNodeName($id);
    }

    public function getNetDevName(int $id): string
    {
        return (string) $this->lms->GetNetDevName($id);
    }

    /**
     * @param mixed $value
     * @return array<mixed>
     */
    private static function arrayOrEmpty($value): array
    {
        return is_array($value) ? $value : [];
    }
}
