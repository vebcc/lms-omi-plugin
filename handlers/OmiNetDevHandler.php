<?php

class OmiNetDevHandler
{
    use \Omi\Handler\PanelHandlerTrait;

    /**
     * @param array<string, mixed> $hook_data
     * @return array<string, mixed>
     */
    public function netDevInfoBeforeDisplay(array $hook_data): array
    {
        $this->assignOnuPanel($hook_data['smarty'], 'networkdevice', 'OltManager device ONU list');

        return $hook_data;
    }
}
