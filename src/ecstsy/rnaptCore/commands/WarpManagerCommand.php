<?php

namespace ecstsy\rnaptCore\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\rnaptCore\utils\uis\WarpUi;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class WarpManagerCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        $sender->sendForm(WarpUi::getWarpManageForm($sender));
    }

    public function getPermission(): string
    {
        return "core.warpmanager";
    }
}