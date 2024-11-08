<?php

namespace ecstsy\rnaptCore\managers;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\utils\QueryStmts;
use ecstsy\rnaptCore\utils\Utils;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
class CoinFlipManager {

    const HEADS = 0;
    
    const TAILS = 1;

    public static array $coinFlips = [];

    public function __construct(public Loader $plugin)
    {
        $this->loadCoinFlips();
    }

    /**
     * Load all coin flips from the database and store them in the $coinFlips array.
     */
    private function loadCoinFlips(): void
    {
        Loader::getDatabase()->executeSelect(QueryStmts::COINFLIP_SELECT, [], function (array $rows): void {
            foreach ($rows as $row) {
                $this->coinFlips[$row["uuid"]] = [
                    "uuid" => $row["uuid"], 
                    "username" => $row["username"],
                    "type" => $row["type"],
                    "wager" => $row["money"]
                ];
            }
        });
    }

    /**
     * Add a new coin flip to the database and cache it.
     *
     * @param Player $player
     * @param int $type
     * @param int $wager
     */
    public static function addCoinFlip(Player $player, int $type, int $wager): void
    {
        $uuid = $player->getUniqueId()->toString();
        $username = $player->getName();

        $args = [
            "uuid" => $uuid,
            "username" => $username,
            "type" => $type,
            "money" => $wager,
        ];

        Loader::getDatabase()->executeInsert(QueryStmts::COINFLIP_CREATE, $args);

        self::$coinFlips[$uuid] = [
            "uuid" => $uuid,
            "username" => $username,
            "type" => $type,
            "wager" => $wager
        ];
    }

    /**
     * Get all coin flips as an array of items to display.
     *
     * @return array
     */
    public static function getCoinFlipsAsItems(): array
    {
        $items = [];
        $lang = Loader::getLanguageManager();

        foreach (self::$coinFlips as $uuid => $coinFlipData) {
            $meta = $coinFlipData["type"] === self::TAILS ? 0 : 1;
            $item = VanillaBlocks::MOB_HEAD()->setMobHeadType(
                $meta === 0 ? MobHeadType::SKELETON() : MobHeadType::WITHER_SKELETON()
            )->asItem();
            $item->setCustomName(C::colorize(str_replace("{player}", $coinFlipData["username"], $lang->getNested("coinflip.item.name"))));
            $loreConfig = $lang->getNested("coinflip.item.lore");

            $lore = str_replace(
                ["{wager}", "{side}"],
                [
                    "$" . number_format(intval($coinFlipData["wager"])), 
                    $meta === 0 ? "Heads" : "Tails" 
                ],
                $loreConfig
            );
    
            $coloredLore = array_map(fn($line) => C::colorize($line), $lore);
    
            $item->setLore($coloredLore);

            $item->getNamedTag()->setString("username", $coinFlipData["username"]);
            $item->getNamedTag()->setString("submitter", $coinFlipData["username"]);
            $item->getNamedTag()->setString("type", $meta === self::HEADS ? "Heads" : "Tails");
            $item->getNamedTag()->setInt("wager", intval($coinFlipData["wager"]));
            $items[] = $item;
        }

        return $items;
    }

    public static function getOppositeHead(Item $head, string $username): Item
    {
        $currentType = $head->getNamedTag()->getTag("type")->getValue();
        
        $newType = $currentType === "Heads" ? "Tails" : "Heads";
        
        $item = VanillaBlocks::MOB_HEAD()->setMobHeadType(
            $newType === "Heads" ? MobHeadType::SKELETON() : MobHeadType::WITHER_SKELETON()
        )->asItem();
        
        $item->setCustomName(C::colorize("{$username}'s {$newType}"));
        $item->getNamedTag()->setString("username", $username);
        $item->getNamedTag()->setString("submitter", $head->getNamedTag()->getTag("submitter")->getValue());
        $item->getNamedTag()->setString("type", $newType);
        $item->getNamedTag()->setInt("wager", $head->getNamedTag()->getTag("wager")->getValue());

        return $item;
    }


    /**
     * Remove a coin flip from the database and cache.
     *
     * @param string $uuid
     */
    public static function removeCoinFlip(string $uuid): void
    {
        Loader::getDatabase()->executeChange(QueryStmts::COINFLIP_DELETE, ["uuid" => $uuid]);

        unset(self::$coinFlips[$uuid]);
    }

    /**
     * Get the prefix for the CoinFlip feature.
     *
     * @return string
     */
    public static function getPrefix(): string
    {
        return C::colorize(Loader::getLanguageManager()->getNested("coinflip.prefix"));
    }

    /**
     * Get the interval in ticks for the coin flip roll task.
     *
     * @return int
     */
    public static function getRollTaskTickInterval(): int
    {
        return 10;
    }

    /**
     * Get the coin flip data for a specific player.
     *
     * @param string $uuid
     * @return array|null
     */
    public function getCoinFlipData(string $uuid): ?array
    {
        return $this->coinFlips[$uuid] ?? null;
    }

    /**
     * Check if a player has already submitted a coin flip.
     *
     * @param Player $player
     * @return bool
     */
    public static function hasSubmittedCoinFlip(Player $player): bool
    {
        return isset(self::$coinFlips[$player->getUniqueId()->toString()]);
    }

    /**
     * Get the coin flip head for a player.
     *
     * @param Player $player
     * @return Item|null
     */
    public static function getPlayerCoinFlipHead(Player $player): ?Item
    {
        if (!self::hasSubmittedCoinFlip($player)) {
            return null;
        }

        $uuid = $player->getUniqueId()->toString();
        $lang = Loader::getLanguageManager();
        $coinFlipData = self::$coinFlips[$uuid] ?? null;
        
        if ($coinFlipData === null) {
            return null; 
        }
        
        $meta = $coinFlipData["type"] === self::TAILS ? 0 : 1;
        $item = VanillaBlocks::MOB_HEAD()->setMobHeadType(
            $meta === 0 ? MobHeadType::SKELETON() : MobHeadType::WITHER_SKELETON()
        )->asItem();

        $item->setCustomName(C::colorize(str_replace("{player}", $coinFlipData["username"], $lang->getNested("coinflip.item.name"))));
        $loreConfig = $lang->getNested("coinflip.item.lore");

        $lore = str_replace(
            ["{wager}", "{side}"],
            [
                "$" . number_format(intval($coinFlipData["wager"])), 
                $meta === 0 ? "Heads" : "Tails" 
            ],
            $loreConfig
        );

        $coloredLore = array_map(fn($line) => C::colorize($line), $lore);

        $item->setLore($coloredLore);

        $nbt = $item->getNamedTag();
        $nbt->setString("username", $coinFlipData["username"]);
        $nbt->setString("submitter", $coinFlipData["username"]);
        $nbt->setString("type", $coinFlipData["type"] === self::HEADS ? "Heads" : "Tails");
        $nbt->setInt("wager", intval($coinFlipData["wager"]));
        $item->setNamedTag($nbt);
        
        return $item;
    }

}