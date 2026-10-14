<?php

class OmiTicketHandler
{
    use \Omi\Handler\PanelHandlerTrait;

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function ticketViewBeforeDisplay(array $hook_data): array
    {
        if (!isset($hook_data['ticket'])) {
            return $hook_data;
        }

        $customerId = (int) ($hook_data['ticket']['customerid'] ?? 0);
        $nodeId = (int) ($hook_data['ticket']['nodeid'] ?? 0);
        if ($customerId <= 0 && $nodeId <= 0) {
            return $hook_data;
        }

        $params = [];
        if ($customerId > 0) {
            $params[] = 'ownerLmsId=' . $customerId;
        }
        if ($nodeId > 0) {
            $params[] = 'deviceLmsId=' . $nodeId;
        }

        $this->assignOnuPanel($hook_data['smarty'], 'ticket', 'OltManager ticket ONU list', implode('&', $params));

        return $hook_data;
    }
}
