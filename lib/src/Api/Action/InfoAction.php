<?php

declare(strict_types=1);

namespace Omi\Api\Action;

use Omi\Api\Action;
use Omi\Api\ApiResponse;
use Omi\Config\OmiConfig;

final class InfoAction implements Action
{
    private string $pluginVersion;
    /** @var list<string> */
    private array $actions;
    private OmiConfig $config;

    /**
     * @param list<string> $actions nazwy pozostałych akcji
     */
    public function __construct(string $pluginVersion, array $actions, OmiConfig $config)
    {
        $this->pluginVersion = $pluginVersion;
        $this->actions = $actions;
        $this->config = $config;
    }

    public function name(): string
    {
        return 'info';
    }

    public function handle(array $params)
    {
        $actions = array_values(array_unique(array_merge([$this->name()], $this->actions)));

        return [
            'pluginVersion' => $this->pluginVersion,
            'apiVersion' => ApiResponse::API_VERSION,
            'actions' => $actions,
            'features' => [
                'sso' => true,
                'conditionalFetch' => true,
                'customerEmail' => $this->config->returnCustomerEmail(),
            ],
        ];
    }
}
