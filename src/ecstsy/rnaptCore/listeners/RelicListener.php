<?php

namespace ecstsy\rnaptCore\listeners;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\managers\RelicsManager;
use ecstsy\rnaptCore\utils\Utils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\TextFormat as C;

class RelicListener implements Listener {

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $relicRarity = RelicsManager::getRandomRelicRarity();
        $lang = Loader::getLanguageManager();
        $color = "";
    
        if ($relicRarity !== null && RelicsManager::chanceToGetRelic($player)) {
            $color = self::$config['rewards'][$relicRarity]['color'] ?? "§f"; 

            $relic = RelicsManager::createPrismarineRelicItem($relicRarity);
            $player->getInventory()->addItem($relic);
            $player->sendMessage(C::colorize(str_replace(["{color}", "{rarity}"], [$color, $relicRarity], $lang->getNested("relics.obtained"))));
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $lang = Loader::getLanguageManager();

        if (RelicsManager::isRelic($item)) {
            $relicRarity = RelicsManager::getRelicRarity($item);
    
            if ($player->getInventory()->contains($item)) {
                $player->getInventory()->removeItem($item->setCount(1));
    
                $rewardsConfig = Utils::getConfiguration("features/relics.yml");
                $rewards = $rewardsConfig->get('rewards', []);
    
                $relicRewards = $rewards[$relicRarity]['items'] ?? [];
    
                if (!empty($relicRewards)) {
                    $rewardItems = Utils::setupItems($relicRewards);
    
                    $rewardItem = $rewardItems[array_rand($rewardItems)];

                    $player->getInventory()->addItem($rewardItem); 
                    $player->sendMessage(C::colorize(str_replace(["{amount}", "{item}"], [$rewardItem->getCount(), $rewardItem->getName()], $lang->getNested("relics.rewarded"))));
                } else {
                    $player->sendMessage(C::colorize(str_replace("{rarity}", $relicRarity, $lang->getNested("relics.no-rewards"))));
                }
            }
        }
    }    
}