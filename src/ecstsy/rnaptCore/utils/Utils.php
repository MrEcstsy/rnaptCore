<?php

namespace ecstsy\rnaptCore\utils;

use ecstsy\rnaptCore\Loader;
use pocketmine\color\Color;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;

class Utils {

    private static array $configCache = [];
   
    public static function getConfiguration(string $fileName): ?Config {
         $pluginFolder = Loader::getInstance()->getDataFolder();
         $filePath = $pluginFolder . $fileName;
 
         if (isset(self::$configCache[$filePath])) {
             return self::$configCache[$filePath];
         }
 
         if (!file_exists($filePath)) {
             Loader::getInstance()->getLogger()->warning("Configuration file '$filePath' not found.");
             return null;
         }
         
         $extension = pathinfo($fileName, PATHINFO_EXTENSION);
 
         switch ($extension) {
             case 'yml':
             case 'yaml':
                 $config = new Config($filePath, Config::YAML);
                 break;
 
             case 'json':
                 $config = new Config($filePath, Config::JSON);
                 break;
 
             default:
                 Loader::getInstance()->getLogger()->warning("Unsupported configuration file format for '$filePath'.");
                 return null;
         }
 
         self::$configCache[$filePath] = $config;
         return $config;
    }
 
     /**
      * Returns an online player whose name begins with or equals the given string (case insensitive).
      * The closest match will be returned, or null if there are no online matches.
      *
      * @param string $name The prefix or name to match.
      * @return Player|null The matched player or null if no match is found.
      */
     public static function getPlayerByPrefix(string $name): ?Player {
         $found = null;
         $name = strtolower($name);
         $delta = PHP_INT_MAX;
 
         /** @var Player[] $onlinePlayers */
         $onlinePlayers = Server::getInstance()->getOnlinePlayers();
 
         foreach ($onlinePlayers as $player) {
             if (stripos($player->getName(), $name) === 0) {
                 $curDelta = strlen($player->getName()) - strlen($name);
 
                 if ($curDelta < $delta) {
                     $found = $player;
                     $delta = $curDelta;
                 }
 
                 if ($curDelta === 0) {
                     break;
                 }
             }
         }
 
         return $found;
     }

     public static function setupItems(array $inputData): array
     {
         $items = [];
         $stringToItemParser = StringToItemParser::getInstance();
     
         foreach ($inputData as $data) {
             $itemString = $data["item"];
             $item = $stringToItemParser->parse($itemString);
     
             if ($item === null) {
                 continue;
             }
     
             $amount = $data["amount"] ?? 1;
             $item->setCount($amount);
     
             $name = $data["name"] ?? null;
             if ($name !== null) {
                 $item->setCustomName(C::colorize($name));
             }
     
             $lore = $data["lore"] ?? null;
             if ($lore !== null) {
                 $lore = array_map(function ($line) {
                     return C::colorize($line);
                 }, $lore);
                 $item->setLore($lore);
             }
     
             $enchantments = $data["enchantments"] ?? null;
             if ($enchantments !== null) {
                 foreach ($enchantments as $enchantmentData) {
                     $enchantment = $enchantmentData["enchant"] ?? null;
                     $level = $enchantmentData["level"] ?? 1;
                     if ($enchantment !== null) {
                         $item->addEnchantment(new EnchantmentInstance(StringToEnchantmentParser::getInstance()->parse($enchantment)), $level);
                     }
                 }
             }
 
             $color = $data["color"] ?? null;
             if ($item instanceof Armor && $color !== null) {
                 $rgb = explode(",", $color);
                 $item->setCustomColor(Color::fromRGB((int)$rgb[0]));
             }
     
             $nbtData = $data["nbt"] ?? null;
             if ($nbtData !== null) {
                 $tag = $nbtData["tag"] ?? "";
                 $value = $nbtData["value"] ?? "";
                 $item->getNamedTag()->setString($tag, $value);
             }
     
             $items[] = $item;
         }
     
         return $items;
     }

    /**
     * @param Item $item
     * @return bool
     */
    public static function hasTag(Item $item, string $name, string $value = null): bool {
        $namedTag = $item->getNamedTag();
        if ($namedTag instanceof CompoundTag) {
            $tag = $namedTag->getTag($name);
            if ($tag instanceof StringTag) {
                if ($value === null) {
                    return true;
                }
                return $tag->getValue() === $value;
            }
        }
        return false;
    }
}