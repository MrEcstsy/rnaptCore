<?php

namespace ecstsy\rnaptCore\managers;

use ecstsy\rnaptCore\utils\Utils;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class RelicsManager {

    private static array $config;

    public static function init(): void {
        self::$config = Utils::getConfiguration("features/relics.yml")->getAll();
    }

    public static function createPrismarineRelic(string $rarity): Item {
        $color = self::$config['rewards'][$rarity]['color'] ?? "§f"; 

        $relic = StringToItemParser::getInstance()->parse(self::$config['relic-item']['item']);

        $relic->setCustomName(C::colorize(str_replace(["{color}", "{rarity}"], [$color, $rarity], self::$config['relic-item']['name'])));

        $lore = array_map(fn($line) => C::colorize(str_replace(["{color}", "{rarity}"], [$color, $rarity], $line)), self::$config['relic-item']['lore']);
        $relic->setLore($lore);
        
        $relic->getNamedTag()->setString("RelicRarity", $rarity);
        
        return $relic;
    }

    public static function createPrismarineRelicItem(string $rarity): Item {
        return self::createPrismarineRelic($rarity);
    }

    public static function getAllRelics(): array {
        return array_keys(self::$config['rewards']);
    }

    public static function isRelic($item): bool {
        if ($item instanceof Item) {
            $tags = $item->getNamedTag();
            $relicRarity = $tags->getString("RelicRarity", "");
            return $relicRarity !== "" && is_string($relicRarity);
        }
        return false;
    }

    public static function getRelicRarity(Item $item): ?string {
        $tags = $item->getNamedTag();
        return $tags->getString("RelicRarity", null);
    }

    public static function getRandomRelicRarity(): ?string {
        $rarities = [];
        foreach (self::$config['rewards'] as $rarity => $data) {
            $rarities[$rarity] = $data['rarity'] ?? 0; 
        }
    
        $totalChance = array_sum($rarities);
        
        $random = mt_rand(1, $totalChance);
    
        foreach ($rarities as $rarity => $chance) {
            if ($random <= $chance) {
                return $rarity;
            }
            $random -= $chance;
        }
    
        return null;
    }
    

    /**
     * Returns whether the player has a chance to get a relic.
     *
     * The chance is currently hardcoded to 10%.
     *
     * @param Player $player
     * @return bool
     */
    public static function chanceToGetRelic(Player $player): bool {
        $chance = 0.1;

        return (mt_rand(1, 100) <= $chance * 100);
    }
}