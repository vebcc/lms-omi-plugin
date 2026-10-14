<?php

declare(strict_types=1);

namespace Omi\Api\Action;

use Omi\Api\Action;
use Omi\Api\ApiException;
use Omi\Pppoe\PppoeCredentialsFinder;
use Omi\Util\MacAddressCorrection;

final class PppoeCredentialsAction implements Action
{
    private PppoeCredentialsFinder $finder;

    public function __construct(PppoeCredentialsFinder $finder)
    {
        $this->finder = $finder;
    }

    public function name(): string
    {
        return 'pppoeCredentials';
    }

    public function handle(array $params)
    {
        $nodeId = isset($params['nodeId']) && is_string($params['nodeId']) ? trim($params['nodeId']) : '';
        $mac = isset($params['mac']) && is_string($params['mac']) ? trim($params['mac']) : '';

        if ($nodeId === '' && $mac === '') {
            throw ApiException::invalidParameter('Parameter nodeId or mac is required');
        }

        if ($nodeId !== '') {
            if (!ctype_digit($nodeId) || (int) $nodeId <= 0) {
                throw ApiException::invalidParameter('Parameter nodeId must be a positive integer');
            }
            $credentials = $this->finder->byNodeId((int) $nodeId);
        } else {
            if (!MacAddressCorrection::isValid($mac)) {
                throw ApiException::invalidParameter('Parameter mac is not a valid MAC address');
            }
            $credentials = $this->finder->byMac(
                $mac,
                self::neighbourCount($params, 'upMacs'),
                self::neighbourCount($params, 'downMacs')
            );
        }

        if ($credentials === null) {
            throw ApiException::notFound('PPPoE credentials not found');
        }

        return $credentials;
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function neighbourCount(array $params, string $name): int
    {
        if (!isset($params[$name]) || $params[$name] === '') {
            return 0;
        }
        $value = $params[$name];
        if (!is_string($value) || !ctype_digit($value) || (int) $value > PppoeCredentialsFinder::MAX_NEIGHBOUR_MACS) {
            throw ApiException::invalidParameter(sprintf(
                'Parameter %s must be an integer 0..%d',
                $name,
                PppoeCredentialsFinder::MAX_NEIGHBOUR_MACS
            ));
        }

        return (int) $value;
    }
}
