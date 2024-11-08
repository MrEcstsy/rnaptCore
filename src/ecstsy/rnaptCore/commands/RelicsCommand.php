<?php

namespace ecstsy\rnaptCore\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\managers\RelicsManager;
use ecstsy\rnaptCore\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class RelicsCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("rarity", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $rarity = isset($args["rarity"]) ? $args["rarity"] : null;
        $lang = Loader::getLanguageManager();
        $config = Utils::getConfiguration("features/relics.yml");
            
        if (!$sender instanceof Player) {
            C::colorize("&r&7In-game only!");
            return;
        }

        if ($rarity !== null) {
            if (in_array($rarity, RelicsManager::getAllRelics(), true)) {
                $relic = RelicsManager::createPrismarineRelicItem($rarity);
                $color = $config->getNested("rewards." . $rarity . ".color") ?? "§f";

                $sender->getInventory()->addItem($relic);
                $sender->sendMessage(C::colorize(str_replace(["{rarity}", "{color}"], [$rarity, $color], $lang->getNested("relics.obtained"))));
            } else {    
                $sender->sendMessage(C::colorize(str_replace("{rarity}", $rarity, $lang->getNested("relics.unknown"))));
                return;
            }
        } else {
            $sender->sendMessage("Available Relics: " . implode(", ", RelicsManager::getAllRelics()));
            return;
        }
    }

    public function getPermission(): string {
        return "core.relics";
    }
}