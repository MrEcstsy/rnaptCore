<?php

declare(strict_types=1);

namespace ecstsy\rnaptCore\player;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\utils\QueryStmts;
use ecstsy\rnaptCore\utils\Utils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\UuidInterface;

final class CorePlayer
{

    private bool $isConnected = false;

    public function __construct(
        private UuidInterface $uuid,
        private string        $username,
        private int           $balance,
        private string        $cooldowns
    )
    {
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function setConnected(bool $connected): void
    {
        $this->isConnected = $connected;
    }

    /**
     * Get UUID of the player
     *
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * This function gets the PocketMine player
     *
     * @return Player|null
     */
    public function getPocketminePlayer(): ?Player
    {
        return Server::getInstance()->getPlayerByUUID($this->uuid);
    }

    /**
     * Get username of the session
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username of the session
     *
     * @param string $username
     * @return void
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
        $this->updateDb(); // Make sure to call updateDb function when you're making changes to the player data
    }

        /**
     * @return int
     */
    public function getBalance(): int
    {
        return $this->balance;
    }

    /**
     * @param int $amount
     * @return void
     */
    public function addBalance(int $amount): void 
    {
        $config = Utils::getConfiguration("features/economy.yml");
        $maxAmount = $config->getNested("settings.max-money");
        $lang = Loader::getLanguageManager();

        $remainingAmount = $maxAmount - $this->balance;
        $amountToAdd = min($amount, $remainingAmount);

        if ($amountToAdd <= 0) {
            $this->getPocketminePlayer()->sendMessage(TextFormat::colorize($lang->getNested("economy.max-money")));
            return;
        }

        $this->balance += $amountToAdd;
        $this->getPocketminePlayer()->sendMessage(TextFormat::colorize(str_replace(["{amount}", "{currency_symbol"], [number_format($amountToAdd), $config->getNested("settings.currency-symbol")], $lang->getNested("economy.add-balance"))));
        $this->updateDb();
    }

    /**
     * @param int $amount
     * @return void
     */
    public function subtractBalance(int $amount): void
    {
        $this->balance -= $amount;
        $this->updateDb();
    }

    /**
     * @param int $amount
     * @return void
     */
    public function setBalance(int $amount): void
    {
        $this->balance = $amount;
        $this->updateDb();
    }

    public function addCooldown(string $cooldownName, int $duration): void
    {
        $cooldowns = json_decode($this->cooldowns, true) ?? [];

        $cooldowns[$this->getUuid()->toString()][$cooldownName] = time() + $duration;

        $this->cooldowns = json_encode($cooldowns);

        $this->updateDb();
    }

    public function getCooldown(string $cooldownName): ?int
    {
        $cooldowns = json_decode($this->cooldowns, true);

        if ($cooldowns !== null && isset($cooldowns[$this->getUuid()->toString()][$cooldownName])) {
            $cooldownExpireTime = $cooldowns[$this->getUuid()->toString()][$cooldownName];
            $remainingCooldown = $cooldownExpireTime - time();
            return max(0, $remainingCooldown);
        }

        return null;
    }

    /**
     * Update player information in the database
     *
     * @return void
     */
    private function updateDb(): void
    {

        Loader::getDatabase()->executeChange(QueryStmts::PLAYERS_UPDATE, [
            "uuid" => $this->uuid->toString(),
            "username" => $this->username,
            "balance" => $this->balance,
            "cooldowns" => $this->cooldowns
        ]);
    }

}