<?php

declare(strict_types=1);

namespace Omi\Api;

use Omi\Support\Logger;

/**
 * Jawny rejestr akcji API v3 - żadnego wywoływania metod po nazwie z requestu.
 */
final class Router
{
    /** @var array<string, Action> */
    private array $actions = [];
    private Logger $logger;

    /**
     * @param list<Action> $actions
     */
    public function __construct(array $actions, Logger $logger)
    {
        foreach ($actions as $action) {
            $this->actions[$action->name()] = $action;
        }
        $this->logger = $logger;
    }

    /**
     * @return list<string>
     */
    public function actionNames(): array
    {
        return array_keys($this->actions);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function dispatch(string $actionName, array $params): ApiResponse
    {
        try {
            if (!isset($this->actions[$actionName])) {
                throw ApiException::unknownAction($actionName);
            }

            return ApiResponse::success($this->actions[$actionName]->handle($params));
        } catch (ApiException $e) {
            return ApiResponse::error($e);
        } catch (\Throwable $e) {
            $this->logger->exception('[omiapi] action=' . substr($actionName, 0, 64), $e);

            return ApiResponse::error(ApiException::internal());
        }
    }
}
