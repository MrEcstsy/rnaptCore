<?php

namespace ecstsy\rnaptCore\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\rnaptCore\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class ShortWarpCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        $warp = Loader::getWarpManager()->getWarp($this->getName());
        $warp->teleport($sender);
    }

    public function getPermission(): string {
        return "core.shortwarp";
    }
}