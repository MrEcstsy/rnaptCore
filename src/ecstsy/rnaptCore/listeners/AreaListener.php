<?php

namespace ecstsy\rnaptCore\listeners;

use ecstsy\rnaptCore\server\areas\AreaManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;

class AreaListener implements Listener {

    private AreaManager $areaManager;

    public function __construct(AreaManager $areaManager) {
        $this->areaManager = $areaManager;
    }

    public function onPlayerExhaust(PlayerExhaustEvent $event): void {
        $player = $event->getPlayer();
        $position = $player->getPosition();

        $area = $this->areaManager->getAreaByPosition($position);

        if ($area !== null && !$area->getSetting("huner", true)) {
            $event->cancel();
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        
    }
}