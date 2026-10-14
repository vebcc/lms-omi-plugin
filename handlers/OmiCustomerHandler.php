<?php

class OmiCustomerHandler
{
    use \Omi\Handler\PanelHandlerTrait;

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function customerInfoBeforeDisplay(array $hook_data): array
    {
        if (!$this->isOnuTabHidden($hook_data)) {
            $this->assignOnuPanel($hook_data['smarty'], 'customer', 'OltManager customer ONU list');
        }

        return $hook_data;
    }

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function customerEditAfterSubmit(array $hook_data): array
    {
        $this->forceSync('customer');

        return $hook_data;
    }

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function customerAddAfterSubmit(array $hook_data): array
    {
        $this->forceSync('customer');

        return $hook_data;
    }

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function customerAssignmentAddAfterSubmit(array $hook_data): array
    {
        $this->forceSync('customer');

        return $hook_data;
    }

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function customerAssignmentEditAfterSubmit(array $hook_data): array
    {
        $this->forceSync('customer');

        return $hook_data;
    }
}
