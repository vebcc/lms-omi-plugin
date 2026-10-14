<?php

class OmiNodeHandler
{
    use \Omi\Handler\PanelHandlerTrait;

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function nodeInfoBeforeDisplay(array $hook_data): array
    {
        if (!$this->isOnuTabHidden($hook_data)) {
            $this->assignOnuPanel($hook_data['smarty'], 'device', 'OltManager device ONU list');
        }

        return $hook_data;
    }

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function nodeAddAfterSubmit(array $hook_data): array
    {
        $this->forceSync('device');

        return $hook_data;
    }

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function nodeEditAfterSubmit(array $hook_data): array
    {
        $this->forceSync('device');

        return $hook_data;
    }
}
