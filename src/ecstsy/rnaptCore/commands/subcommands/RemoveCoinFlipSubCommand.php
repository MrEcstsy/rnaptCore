<?php

namespace ecstsy\rnaptCore\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\managers\CoinFlipManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class RemoveCoinFlipSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            C::colorize("&r&7In-game only!");
            return;
        }   

        if (CoinFlipManager::hasSubmittedCoinFlip($sender)) {
            $head = CoinFlipManager::getPlayerCoinFlipHead($sender);

            $wager = $head->getNamedTag()->getTag("wager")->getValue();
            $session = Loader::getPlayerManager()->getSession($sender);
            $session->addBalance($wager);
            CoinFlipManager::removeCoinFlip($sender->getUniqueId()->toString());
            $sender->sendMessage(C::colorize(str_replace("{prefix}", CoinFlipManager::getPrefix(), Loader::getLanguageManager()->getNested("coinflip.removed"))));
            return;
        } else {
            $sender->sendMessage(C::colorize(str_replace("{prefix}", CoinFlipManager::getPrefix(), Loader::getLanguageManager()->getNested("coinflip.not-submitted"))));
            return;
        }
    }

    public function getPermission(): ?string
    {
        return "core.default";
    }
}