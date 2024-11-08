<?php

namespace ecstsy\rnaptCore\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\server\warps\Warp;
use ecstsy\rnaptCore\utils\uis\WarpUi;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class WarpCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("warp", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $lang = Loader::getLanguageManager();
        $warpManager = Loader::getWarpManager();

        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        if (!isset($args["warp"])) {
            $sender->sendForm(WarpUi::getWarpMenuForm($sender));
            return;
        }

        if ($warpManager->getWarp($args["warp"]) === null) {
            $sender->sendMessage(C::colorize($lang->getNested("warps.error")));
            return;
        }

        $warp = $warpManager->getWarp($args["warp"]);

        $warp->teleport($sender);
    }

    public function getPermission(): string {
        return "core.warp";
    }
}