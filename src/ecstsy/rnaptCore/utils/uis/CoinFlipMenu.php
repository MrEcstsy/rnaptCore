<?php

namespace ecstsy\rnaptCore\utils\uis;

use ecstsy\AdvancedEnchantments\libs\muqsit\invmenu\type\InvMenuTypeIds;
use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\managers\CoinFlipManager;
use ecstsy\rnaptCore\tasks\CoinFlipRollTask;
use ecstsy\rnaptCore\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\TextFormat as C;

class CoinFlipMenu {

    public static function getCoinFlipMenu(): InvMenu 
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName(C::colorize(CoinFlipManager::getPrefix()));
        $menu->getInventory()->setContents(CoinFlipManager::getCoinFlipsAsItems());
        
        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use($menu): void {
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $lang = Loader::getLanguageManager();
            $session = Loader::getPlayerManager()->getSession($player);

            if ($itemClicked->getTypeId() === VanillaBlocks::MOB_HEAD()->asItem()->getTypeId()) {
                $namedTag = $itemClicked->getNamedTag();

                if ($itemClicked && Utils::hasTag($itemClicked, "username") && Utils::hasTag($itemClicked, "type")) {
                    $username = $namedTag->getTag("username");
                    $wager = $namedTag->getTag("wager");
                    $submitter = $namedTag->getTag("submitter");
                    
                    if ($username->getValue() === $player->getName()) {
                        $player->sendMessage(C::colorize(str_replace('{prefix}', CoinFlipManager::getPrefix(), $lang->getNested("coinflip.cannot-flip-yourself"))));
                        return;
                    }

                    if ($session->getBalance() < $wager->getValue()) {
                        $player->sendMessage(C::colorize(str_replace('{prefix}', CoinFlipManager::getPrefix(), $lang->getNested("coinflip.not-enough-money"))));
                        return;
                    }

                    $session->subtractBalance($wager->getValue());
                    $menu->getInventory()->removeItem($itemClicked);
                    $player->removeCurrentWindow();
                    $pSubmitter = Loader::getInstance()->getServer()->getPlayerExact((string) $submitter->getValue());
                    CoinFlipManager::removeCoinFlip($pSubmitter->getUniqueId()->toString());
                    $p = Loader::getInstance()->getServer()->getPlayerExact((string) $username->getValue());
                    
                    $rollMenu = CoinFlipRollMenu::getCoinFlipRollMenu($itemClicked);
                    if ($p !== null) {
                        $rollMenu->send($p);
                    }
                    $rollMenu->send($player);

                    Loader::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new CoinFlipRollTask($rollMenu, $itemClicked, $player), 20, CoinFlipManager::getRollTaskTickInterval());
                }
            }

            return;
        }));

        return $menu;
    }
}