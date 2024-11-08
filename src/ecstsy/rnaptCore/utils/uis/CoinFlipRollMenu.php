<?php

namespace ecstsy\rnaptCore\utils\uis;

use ecstsy\rnaptCore\managers\CoinFlipManager;
use ecstsy\rnaptCore\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class CoinFlipRollMenu
{

    public static function getCoinFlipRollMenu(Item $head): InvMenu
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_HOPPER);
        $menu->setName(C::colorize(CoinFlipManager::getPrefix()));

        for ($i = 0; $i < $menu->getInventory()->getSize(); $i++) {
            $menu->getInventory()->addItem(VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("|" . str_repeat("\0x", $i)));
        }
        $menu->setListener(InvMenu::readonly());
        $menu->getInventory()->setItem(2, $head);
        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use($menu): void {
            $item = $inventory->getItem(0);
            if ($player->isOnline() && !$item->getNamedTag()->getTag("ended")) {
                $menu->send($player);
            }
        });

        return $menu;
    }
}