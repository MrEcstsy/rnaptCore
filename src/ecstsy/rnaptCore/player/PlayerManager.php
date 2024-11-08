<?php

declare(strict_types=1);

namespace ecstsy\rnaptCore\player;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\utils\QueryStmts;
use ecstsy\rnaptCore\utils\Utils;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class PlayerManager
{
    use SingletonTrait;

    /** @var CorePlayer[] */
    private array $sessions; // array to fetch player data

    public function __construct(
        public Loader $plugin
    ){
        self::setInstance($this);

        $this->loadSessions();
    }

    /**
     * Store all player data in $sessions property
     *
     * @return void
     */
    private function loadSessions(): void
    {
        Loader::getDatabase()->executeSelect(QueryStmts::PLAYERS_SELECT, [], function (array $rows): void {
            foreach ($rows as $row) {
                $this->sessions[$row["uuid"]] = new CorePlayer(
                    Uuid::fromString($row["uuid"]),
                    $row["username"],
                    $row["balance"],
                    $row["cooldowns"]
                );
            }
        });
    }

    /**
     * Create a session
     *
     * @param Player $player
     * @return CorePlayer
     * @throws \JsonException
     */
    public function createSession(Player $player): CorePlayer
    {
        $args = [
            "uuid" => $player->getUniqueId()->toString(),
            "username" => $player->getName(),
            "balance" => Utils::getConfiguration("features/economy.yml")->getNested("settings.starting-balance"),
            "cooldowns" => "{}",
        ];

        Loader::getDatabase()->executeInsert(QueryStmts::PLAYERS_CREATE, $args);

        $this->sessions[$player->getUniqueId()->toString()] = new CorePlayer(
            $player->getUniqueId(),
            $args["username"],
            $args["balance"],
            $args["cooldowns"]
        );
        return $this->sessions[$player->getUniqueId()->toString()];
    }

    /**
     * Get session by player object
     *
     * @param Player $player
     * @return CorePlayer|null
     */
    public function getSession(Player $player) : ?CorePlayer
    {
        return $this->getSessionByUuid($player->getUniqueId());
    }

    /**
     * Get session by player name
     *
     * @param string $name
     * @return CorePlayer|null
     */
    public function getSessionByName(string $name) : ?CorePlayer
    {
        foreach ($this->sessions as $session) {
            if (strtolower($session->getUsername()) === strtolower($name)) {
                return $session;
            }
        }
        return null;
    }

    /**
     * Get session by UuidInterface
     *
     * @param UuidInterface $uuid
     * @return CorePlayer|null
     */
    public function getSessionByUuid(UuidInterface $uuid) : ?CorePlayer
    {
        return $this->sessions[$uuid->toString()] ?? null;
    }

    public function destroySession(CorePlayer $session) : void
    {
        Loader::getDatabase()->executeChange(QueryStmts::PLAYERS_DELETE, ["uuid", $session->getUuid()->toString()]);

        # Remove session from the array
        unset($this->sessions[$session->getUuid()->toString()]);
    }

    public function getSessions() : array
    {
        return $this->sessions;
    }

}