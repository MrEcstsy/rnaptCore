<?php

namespace ecstsy\rnaptCore\commands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\rnaptCore\commands\subcommands\RemoveCoinFlipSubCommand;
use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\managers\CoinFlipManager;
use ecstsy\rnaptCore\utils\uis\CoinFlipMenu;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class CoinFlipCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerSubCommand(new RemoveCoinFlipSubCommand("remove", "Remove your coinflip"));
        $this->registerArgument(0, new IntegerArgument("amount", true));
        $this->registerArgument(1, new RawStringArgument("side", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        $amount = isset($args["amount"]) ? $args["amount"] : null;
        $side = isset($args["side"]) ? $args["side"] : null;
        $lang = Loader::getLanguageManager();
        $session = Loader::getPlayerManager()->getSession($sender);

        if ($amount !== null) {
            if ($side !== null) {
                if (CoinFlipManager::hasSubmittedCoinFlip($sender)) {
                    $sender->sendMessage(C::colorize(str_replace("{prefix}", CoinFlipManager::getPrefix(), $lang->getNested("coinflip.already-submitted"))));
                    return;
                } elseif (!CoinFlipManager::hasSubmittedCoinFlip($sender)) {
                    $types = ["heads", "tails"];

                    if (in_array(strtolower($side), $types)) {
                        $wager = abs(intval($amount));
                        
                        if ($session->getBalance() < $wager) {
                            $sender->sendMessage(C::colorize(str_replace("{prefix}", CoinFlipManager::getPrefix(), $lang->getNested("coinflip.not-enough-money"))));
                            return;
                        }

                        $session->subtractBalance($wager);
                        $type = strtolower($side) === "heads" ? CoinFlipManager::HEADS : CoinFlipManager::TAILS;
                        CoinFlipManager::addCoinFlip($sender, $type, $wager);
                        $sender->sendMessage(C::colorize(str_replace("{prefix}", CoinFlipManager::getPrefix(), $lang->getNested("coinflip.submitted"))));
                    } else {
                        $sender->sendMessage(C::colorize(CoinFlipManager::getPrefix() . C::GRAY . $this->getUsage()));
                    }
                } else {
                    $sender->sendMessage(C::colorize(CoinFlipManager::getPrefix() . C::GRAY . $this->getUsage()));
                }
            } else {
                $sender->sendMessage(C::colorize(CoinFlipManager::getPrefix() . C::GRAY . $this->getUsage()));
                return;
            }
        } else {
            CoinFlipMenu::getCoinFlipMenu()->send($sender);
            $sender->sendMessage(C::colorize(str_replace("{prefix}", CoinFlipManager::getPrefix(), $lang->getNested("coinflip.opening"))));
            return;
        }
    }

    public function getPermission(): string
    {
        return "core.default";
    }
}