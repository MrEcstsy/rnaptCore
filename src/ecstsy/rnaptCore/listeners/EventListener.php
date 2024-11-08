<?php

namespace ecstsy\rnaptCore\listeners;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\player\PlayerManager;
use ecstsy\rnaptCore\utils\Utils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat as C;
use SplObjectStorage;

class EventListener implements Listener {

    public float $combatTime;

    public bool $quitKill;

    public bool $banAllCommands;

    /** @var SplObjectStorage<Player, int> */
    private SplObjectStorage $playersInCombat;

    /** @var array<string> */
    private array $bannedCommands = [];

    public function __construct() {
        $this->playersInCombat = new SplObjectStorage();
        $this->combatTime = Utils::getConfiguration("features/combatLog.yml")->getNested("settings.CombatTime");
        $this->quitKill = Utils::getConfiguration("features/combatLog.yml")->getNested("settings.KillOnLogout");
        $this->banAllCommands = Utils::getConfiguration("features/combatLog.yml")->getNested("settings.BanAllCommands");
        foreach (Utils::getConfiguration("features/combatLog.yml")->getNested("settings.BannedCommands") as $cmd)
        {
            $this->bannedCommands[] = $cmd;
        }        
        $this->combatTask();
    }

    public function onLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();

        if (PlayerManager::getInstance()->getSession($player) === null) {
            PlayerManager::getInstance()->createSession($player);
        }
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        PlayerManager::getInstance()->getSession($player)->setConnected(true);

        $session = PlayerManager::getInstance()->getSession($player);
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();

        PlayerManager::getInstance()->getSession($player)->setConnected(false);

        if ($this->playersInCombat->contains($player)) {
            if ($this->quitKill) {
                $player->kill();
            }
            $this->playersInCombat->detach($player);
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onDamage(EntityDamageByEntityEvent $event): void {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        $lang = Loader::getLanguageManager();

        if ($event->isCancelled() || !$player instanceof Player || !$damager instanceof Player) {
            return;
        }

        foreach ([$player, $damager] as $combatant) {
            if (!$this->playersInCombat->contains($combatant)) {
                $combatant->sendMessage(C::colorize($lang->getNested("combat-logger.enter-combat")));
            }
            $this->playersInCombat[$combatant] = $this->combatTime;
        }
    }

    public function onCommandPreprocess(CommandEvent $event): void {
        $player = $event->getSender();
        if (!$player instanceof Player) return;

        $msg = explode(" ", $event->getCommand());
        $lang = Loader::getLanguageManager();

        if ($this->playersInCombat->contains($player)) {
            if ($this->banAllCommands || in_array($msg[0], $this->bannedCommands, true)) {
                $player->sendMessage(C::colorize($lang->getNested("combat-logger.banned-command")));
                $event->cancel();
            }
        }
    }


    public function combatTask(): void {
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                $lang = Loader::getLanguageManager();

                foreach ($this->playersInCombat as $player) {
                    $time = $this->playersInCombat[$player] - 1;
                    if ($time <= 0) {
                        $this->playersInCombat->detach($player);
                        $player->sendMessage(C::colorize($lang->getNested("combat-logger.exit-combat")));
                    } else {
                        $this->playersInCombat[$player] = $time;
                    }
                }
            }
        ), 20);
    }
}